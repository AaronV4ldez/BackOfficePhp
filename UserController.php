<?php

// UserController.php
// Funciones de manejo de usuarios
// LineaExpressApp

namespace Controllers;

class UserController extends BaseController
{
    //cambio 12/02/24
    public const REQ_PARAMS_SIGNUP = [ 'fullname', 'email', 'phone', 'cc','password' ]; //, 'password'
    public const REQ_PARAMS_VALIDATION = [ 'email', 'activation_code' ];
    public const REQ_CHANGE_PASSWORD = [ 'current_password', 'new_password' ];
    public const REQ_RESET_PASSWORD = [ 'email' ];
    public const REQ_SAVE_DEVICE_ID = [ 'email', 'device_id' ];
    public const REQ_NEW_PW_USER = ['userlogin', 'password', 'fullname', 'phone', 'usertype'];
    public const REQ_UPDATE_PW_USER = ['id'];

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function mobileUserSignup()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_PARAMS_SIGNUP, $params);

        if ( strlen( $params[ 'password' ] ) < 8 ) {
             http_response_code( 400 );
             echo json_encode( [ 'message' => 'Password debe ser de 8 (o mas) caracteres.' ] );
             die;
         }

        if (!filter_var($params[ 'email' ], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Direccion de correo electrónico no está en formato correcto.' ]);
            die;
        }

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($params[ 'email' ]);
        if ($user !== false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario ya existe.' ]);
            die;
        }
        //cambio 13/02/24
        $rndPass = $this->removeSpaces($params[ 'password' ]);
        $cc = $this->removeNonNumericChars($params[ 'cc' ]);
        $ph = $this->removeNonNumericChars($params[ 'phone' ]);

        $user = $um->createUser(
            $params[ 'fullname' ],
            $params[ 'email' ],
            //cambio 12/02/24
            //$params[ 'password' ],
            $ph,
            $rndPass,
            1,
            $cc
        );

        // var_dump( $user );
        $user_id = $user[ 'id' ];
        $smscode = $um->createUserActivationRecord($user_id);

        if ($user[ 'usertype' ] === 1) {

            if ($cc == '52') {
                $sms_result = \SMSApi::sendSMS(
                    $ph,
                    'El codigo para activar su cuenta de ' . \Util\Mail::getAppName() . ' es: ' . $smscode,
                    $cc
                );
            } //cambio 29/02/24 
            else if($cc == '1'){
                $sms_result = \SMSApi::sendSMSusa(
                    $ph,
                    'El codigo para activar su cuenta de ' . \Util\Mail::getAppName() . ' es: ' . $smscode,
                    '52'
                );
            } else {
                $usr = new \Data\UserModel();
                $usr->load(['id = ?', $user_id]);
                $usr->sms_validated = 1;
                //cambio 13/02/24
                //$rndPass = $this->generateRandomString(8);
                $usr->password_hash = password_hash($rndPass, PASSWORD_DEFAULT);
                $usr->save();
                \Util\Mail::sendVerificationEmail($user[ 'userlogin' ], $user[ 'fullname' ], $user[ 'activation_token' ]);
            }

        }

        echo json_encode([
            'message' => 'Usuario registrado exitosamente.',
            'sms' => $sms_result
        ]);
    }

    public function requestNewVerificationCode()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        if (!array_key_exists('email', $params) || empty($params[ 'email' ]) || !array_key_exists('phone', $params) || empty($params[ 'phone' ])) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Debe especificar el correo y telefono del usuario a verificar.' ]);
            die;
        }

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($params[ 'email' ]);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        if ($user->account_verified > 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Cuenta ya ha sido previamente verificada.' ]);
            die;
        }

        if ($user->phone !== $params[ 'phone' ]) {
            $user->phone = $params[ 'phone' ];
            $user->save();
        }

        $smscode = $um->createUserActivationRecord($user->id);

        $sms_result = \SMSApi::sendSMS(
            $this->removeNonNumericChars($user->phone),
            'El codigo para activar su cuenta de ' . \Util\Mail::getAppName() . ' es: ' . $smscode,
            $user->country_code
        );

        echo json_encode([ 'message' => 'Codigo de activacion enviado.', 'sms' => $sms_result ]);
    }

    public function htmlResponse($username, $msg)
    {
        $htmlc = "<html><body>" . file_get_contents('/var/www/apis/api/v1/src/util/emailtemplate.html') . "</body></html>";
        $arr = ['__fullname__' => $username, '__content__' => $msg];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        \http_response_code(200);
        \header('Content-Type: text/html');
        echo $htmlBody;
    }

    public function validateMobileUserWithToken()
    {
        $token = $this->f3->get('PARAMS.token');
        // var_dump( $token );
        if (empty($token)) {
            // http_response_code(400);
            // echo json_encode([ 'message' => 'Debe especificar el token de activacion.' ]);
            $this->htmlResponse('Usuario(a)', 'Debe especificar el token de activacion.');
            die;
        }

        $um = new \Data\UserModel();
        $user = $um->getUserByActivationToken($token);
        if ($user === false) {
            // http_response_code(400);
            // echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            $this->htmlResponse('Usuario(a)', 'Token inexistente.');
            die;
        }

        if ($user->account_verified > 0) {
            // http_response_code(400);
            // echo json_encode([ 'message' => 'Cuenta ya ha sido previamente verificada.' ]);
            $this->htmlResponse($user->fullname, 'Su cuenta ya ha sido previamente verificada.');
            die;
        }

        if ($user->activation_token != $token) {
            // http_response_code(400);
            // echo json_encode([ 'message' => 'Token de activacion invalido.' ]);
            $this->htmlResponse($user->fullname, 'El Token de activacion es invalido.');
            die;
        }

        $user->account_verified = 1;
        $user->save();

        if ($user['account_verified'] === 1 && $user['sms_validated'] === 1 && $user['user_pass_set'] === 0) {
            //cambio 13/02/24
            //$rndPass = $this->generateRandomString(8);
            $rndPass = $this->removeSpaces($user[ 'password' ]); //nuevo
            $user->password_hash = password_hash($rndPass, PASSWORD_DEFAULT);
            $user->save();

            // \Util\Mail::sendRandomPassword($user[ 'userlogin' ], $user[ 'fullname' ], $rndPass); 
            //\Util\Mail::sendVerificationEmail($params[ 'email' ], $params[ 'fullname' ], $user[ 'activation_token' ]);
        }

        // echo json_encode( [ 'message' => 'Cuenta verificada exitosamente.' ] );
        // Cambio $this->htmlResponse($user->fullname, "Su correo electronico fue verificado exitosamente. Usted recibira una contraseña temporal en su bandeja de correo electronico o bandeja de spam. Utilicela para ingresar a la aplicacion y luego cambiela por una de su preferencia.");
    }

    public function validateMobileUser()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(UserController::REQ_PARAMS_VALIDATION, $params);

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($params[ 'email' ]);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        if ($user->sms_validated > 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Cuenta ya ha sido previamente verificada.' ]);
            die;
        }

        $ua = new \DB\SQL\Mapper($this->f3->get('DB'), 'user_activations');
        $ua->load(
            array( 'id_user = :id_user', ':id_user' => $user->id ),
            array( 'order' => ' id desc', 'limit' => 1 )
        );

        if ($ua === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Codigo de activacion es invalido (NE).' ]);
            die;
        }

        if ($ua->activation_code !== $params[ 'activation_code' ]) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Codigo de activacion es invalido. (!=)' ]);
            $ua->last_try_dt = date('Y-m-d h:i:s', time());
            $ua->number_of_tries += 1;
            $ua->save();
            die;
        }

        $ua->activation_dt = date('Y-m-d h:i:s', time());
        $ua->last_try_dt = date('Y-m-d h:i:s', time());
        $ua->number_of_tries += 1;
        $ua->save();

        $user->sms_validated = 1;
        $user->user_pass_set = 1;
        //cambio 05 03 24 para eliminar la verificación por correo
        $user->account_verified = 1;
        $user->save();

        if ($user['sms_validated'] === 1 /*&& $user['user_pass_set'] === 1*/) { //cambio de user pass set de 0 a 1 para que envie email de verificacion
            //cambio 13/02/24
            //$rndPass = $this->generateRandomString(8);
            //cambio, las lineas de abajo estaban descomentadas
            //$rndPass = $this->removeSpaces($params[ 'password' ]); //nuevo
            //$user->password_hash = password_hash($rndPass, PASSWORD_DEFAULT);
            $user->save();

            // \Util\Mail::sendRandomPassword($user[ 'userlogin' ], $user[ 'fullname' ], $rndPass);
            \Util\Mail::sendVerificationEmail($user[ 'userlogin' ], $user[ 'fullname' ], $user[ 'activation_token' ]);
        }

        echo json_encode([ 'message' => 'Cuenta verificada exitosamente.'  ]); // . \var_export($user->cast(), true)
    }

    public function changeUserPassword(): void
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(UserController::REQ_CHANGE_PASSWORD, $params);

        $um = new \Data\UserModel();
        $user = $um->getUserById($this->auth->getUserId());
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        if ($user['account_verified'] === 0 || $user['sms_validated'] === 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Cuenta no ha sido verificada. Asegurese de vefiricar su cuenta mediante SMS y correo.' ]);
            die;
        }

        // check is old password is correct
        if (!password_verify($params[ 'current_password' ], $user->password_hash)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Contraseña actual es incorrecta.' ]);
            die;
        }

        // set new password and save
        $user->password_hash = password_hash($params[ 'new_password' ], PASSWORD_DEFAULT);
        $user['user_pass_set'] = 1;
        $user->save();

        echo json_encode([ 'message' => 'Contraseña cambiada exitosamente.' ]);
    }

    public function resetUserPassword(): void
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(UserController::REQ_RESET_PASSWORD, $params);

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($params[ 'email' ]);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        $reset_data = $um->createPasswordResetRecord($user->userlogin, $user->fullname);
        $token = $reset_data[ 'token' ];
        $smscode = $reset_data[ 'smscode' ];

        if ($user->country_code != '52') {
            \Util\Mail::sendPasswordResetCodeEmail($user->userlogin, $user->fullname, $smscode);
        }
        
        \Util\Mail::sendPasswordResetEmail($user->userlogin, $user->fullname, $token);

        //cambio 29 feb 24
        /*$sms_result = \SMSApi::sendSMSusa(
            $this->removeNonNumericChars($user->phone),
            'El codigo para restablecer su contraseña de ' . \Util\Mail::getAppName() . ' es: ' . $smscode,
            $user->country_code
        );*/

        echo json_encode([ 'message' => 'Proceso de reseteo de contraseña fue iniciado. Por favor revise su correo (tambien carpeta de spam)' ]);
    }

    public function getResetToken()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['token'], $params);

        $userdata = $this->f3->get('DB')->exec('select u.fullname, pr.email, pr.token, pr.sms_code ' .
            'from users u join password_reset pr on pr.email=u.userlogin where pr.token = ?', $params['token']);

        // if $userdata is empty, return error 400
        if (empty($userdata)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No hay informacion para poder procesar su solicitud de restablecer contraseña.' ]);
            die;
        }

        echo json_encode($userdata[0]);
    }

    public function getEmailChangeToken()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['token'], $params);

        $userdata = $this->f3->get('DB')->exec('select u.fullname, ec.newemail, ec.oldemail, ec.token, ec.sms_code ' .
            'from users u join email_change ec on ec.oldemail=u.userlogin where ec.token = ?', $params['token']);

        // if $userdata is empty, return error 400
        if (empty($userdata)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No hay informacion para poder procesar su solicitud de cambio de correo.' ]);
            die;
        }

        echo json_encode($userdata[0]);
    }

    public function updatePass()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['token', 'password', 'smscode'], $params);

        $userdata = $this->f3->get('DB')->exec('select u.fullname, pr.email, pr.token, pr.sms_code ' .
            'from users u join password_reset pr on pr.email=u.userlogin where pr.token = ?', $params['token']);

        // if $userdata is empty, return error 400
        if (empty($userdata)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No hay informacion para poder procesar su solicitud de restablecer contraseña.' ]);
            die;
        }

        $u = $userdata[0];

        if ($u['sms_code'] != $params['smscode']) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Codigo de activacion es invalido.' ]);
            die;
        }

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($u['email']);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        // set new password and save
        $user->password_hash = password_hash($params[ 'password' ], PASSWORD_DEFAULT);
        $user['user_pass_set'] = 1;
        $user->save();

        $this->f3->get('DB')->exec('delete from password_reset where token = ?', $params['token']);

        \Util\Mail::sendPassResetSuccessEmail($user->userlogin, $user->fullname);

        echo json_encode([ 'message' => 'Contraseña cambiada exitosamente.' ]);
    }

    public function updateEmail()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['token', 'smscode'], $params);

        $userdata = $this->f3->get('DB')->exec('select u.fullname, ec.newemail, ec.oldemail, ec.token, ec.sms_code ' .
            'from users u join email_change ec on ec.oldemail=u.userlogin where ec.token = ?', $params['token']);

        // if $userdata is empty, return error 400
        if (empty($userdata)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No hay informacion para poder procesar su solicitud de actualizar correo.' ]);
            die;
        }

        $u = $userdata[0];

        if ($u['sms_code'] != $params['smscode']) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Codigo de activacion es invalido.' ]);
            die;
        }

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($u['oldemail']);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        // set new password and save
        $user->userlogin = $u['newemail'];
        $user->save();

        $this->f3->get('DB')->exec('delete from email_change where token = ?', $params['token']);

        \Util\Mail::sendEmailChangeSuccessEmail($user->userlogin, $user->fullname);

        echo json_encode([ 'message' => 'Correo actualizado exitosamente.' ]);
    }


    public function saveDeviceID()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(UserController::REQ_SAVE_DEVICE_ID, $params);

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($params[ 'email' ]);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        $user->device_id = $params[ 'device_id' ];
        $user->save();

        echo json_encode([ 'message' => 'Dispositivo registrado exitosamente.' ]);
    }

    public function updateSentriData(): void
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(["sentri"], $params);

        $um = new \Data\UserModel();
        $user = $um->getUserById($this->auth->getUserId());
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        $user->sentri_number = $params[ 'sentri' ];
        if (isset($params[ 'sentri_exp_date' ])) {
            $user->sentri_exp_date = $params[ 'sentri_exp_date' ];
        }

        $user->save();

        echo json_encode([ 'message' => 'Cambio realizado exitosamente.' ]);
    }

    public function updateInvoiceData(): void
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(["fac_razon_social", "fac_rfc", "fac_dom_fiscal", "fac_email", "fac_telefono", "fac_cp"], $params);

        $um = new \Data\UserModel();
        $user = $um->getUserById($this->auth->getUserId());
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        $user->fac_razon_social = $params[ 'fac_razon_social' ];
        $user->fac_rfc = $params[ 'fac_rfc' ];
        $user->fac_dom_fiscal = $params[ 'fac_dom_fiscal' ];
        $user->fac_email = $params[ 'fac_email' ];
        $user->fac_telefono = $params[ 'fac_telefono' ];
        $user->fac_cp = $params[ 'fac_cp' ];
        $user->save();

        echo json_encode([ 'message' => 'Cambio realizado exitosamente.' ]);
    }

    public function emailChangeRequest()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['newemail'], $params);

        $um = new \Data\UserModel();
        $user = $um->getUserById($this->auth->getUserId());
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        // var_dump($user->cast());
        if ($user['account_verified'] === 0 || $user['sms_validated'] === 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Cuenta no ha sido verificada. Asegurese de vefiricar su cuenta mediante SMS y correo.' ]);
            die;
        }

        $testUser = $um->getUserByEmail($params['newemail']);

        // check is old password is correct
        if ($testUser !== false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'El correo electronico al que desea cambiar ya esta en uso. Intente con uno distinto.' ]);
            die;
        }

        $reset_data = $um->createEmailChangeRecord($params['newemail'], $user->userlogin);
        $token = $reset_data[ 'token' ];
        $smscode = $reset_data[ 'smscode' ];

        \Util\Mail::sendEmailChangeEmail($params['newemail'], $user->fullname, $token);

        $sms_result = \SMSApi::sendSMS(
            $this->removeNonNumericChars($user->phone),
            'El codigo para cambiar el correo de su cuenta de ' . \Util\Mail::getAppName() . ' App es: ' . $smscode,
            $user->country_code
        );


        echo json_encode([ 'message' => 'Proceso de cambio de correo iniciado, se le enviara un correo a su nueva direccion para continuar con el proceso.' ]);
    }

    public function getPanelWebUserList()
    {
        $this->hasAuthOrDie();

        $ru = $this->getUserData($this->auth->getUserId());
        if ($ru === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'La petición la realizó un usuario no válido.' ]);
            die;
        }

        $this->checkCurrentUserIdAdminOrDie($ru);

        $users = $this->f3->get('DB')->exec('select u.id, u.fullname, u.userlogin, u.phone, u.is_active, ut.utname usertype ' .
            'from users u join user_types ut on ut.id = u.usertype where u.usertype > 1 order by u.is_active desc, u.id');

        echo json_encode($users);
    }

    public function getPanelWebUser() // gets single user data
    {
        $this->hasAuthOrDie();

        $ru = $this->getUserData($this->auth->getUserId());
        if ($ru === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'La petición la realizó un usuario no válido.' ]);
            die;
        }

        $this->checkCurrentUserIdAdminOrDie($ru);

        $um = new \Data\UserModel();
        $user = $um->getUserById($this->f3->get('PARAMS.id'));
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        $user = $user->cast();
        unset($user['password_hash']);
        unset($user['sentri_exp_date']);
        unset($user['activation_token']);

        $user['user_pass_set'] = $user['user_pass_set'] === 1 ? true : false;
        $user['account_verified'] = $user['account_verified'] === 1 ? true : false;
        $user['sms_validated'] = $user['sms_validated'] === 1 ? true : false;

        echo json_encode($user);
    }

    public function createPanelWebUser(): void
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(self::REQ_NEW_PW_USER, $params);

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($params[ 'userlogin' ]);
        if ($user !== false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario ya existe.' ]);
            die;
        }

        // compare password with password confirmation
        if ($params[ 'password' ] !== $params[ 'password_validation' ]) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Las contraseñas no coinciden.' ]);
            die;
        }

        $user = $um->createUser(
            $params[ 'fullname' ],
            $params[ 'userlogin' ],
            $this->removeNonNumericChars($params[ 'phone' ]),
            $params[ 'password' ],
            $params['usertype']
        );
        // $user->save();

        echo json_encode([ 'message' => 'Usuario creado exitosamente.' ]);
    }

    public function updatePanelWebUser()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $ru = $this->getUserData($this->auth->getUserId());
        if ($ru === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'La petición la realizó un usuario no válido.' ]);
            die;
        }
        $this->checkCurrentUserIdAdminOrDie($ru);

        $params = $this->getParamsFromRequestBodyOrDie();

        $um = new \Data\UserModel();
        $user = $um->getUserById($this->f3->get('PARAMS.id'));
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'El usuario que desea actiualizar no existe.' ]);
            die;
        }

        $updateable = [
            'fullname',
            'phone',
            'usertype',
            'userlogin'
        ];

        // check if any param is in updateable adrray and update their value
        foreach ($params as $key => $value) {
            if (in_array($key, $updateable)) {
                $user->$key = $value;
            }
        }

        // password and password validation are checked
        if (isset($params['password']) && isset($params['password_validation'])) {
            if ($params['password'] !== $params['password_validation']) {
                http_response_code(400);
                echo json_encode([ 'message' => 'Las contraseñas no coinciden.' ]);
                die;
            }

            if ($params['password'] !== "" &&  $params['password_validation'] !== "") {
                $user->password_hash = password_hash($params['password'], PASSWORD_DEFAULT);
                $user->user_pass_set = 1;
            }
        }

        $user->save();

        echo json_encode([ 'message' => 'Usuario actualizado exitosamente.' ]);
    }

    public function removePanelWebUser()
    {
        // does NOT remove, onli deactivates
        $this->hasAuthOrDie();
        // $this->checkPostParamsOrDie();

        $ru = $this->getUserData($this->auth->getUserId());
        if ($ru === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'La petición la realizó un usuario no válido.' ]);
            die;
        }
        $this->checkCurrentUserIdAdminOrDie($ru);

        $user_id = \intval($this->f3->get('PARAMS.id'));

        $um = new \Data\UserModel();
        $user = $um->getUserById($user_id);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario que desea eliminar no existe.' ]);
            die;
        }

        $user->is_active = 0;
        $user->save();

        echo json_encode([ 'message' => 'Usuario eliminado exitosamente.' ]);
    }

    public function removeUser()
    {
        $this->hasAuthOrDie();

        $user = $this->getUserData($this->auth->getUserId());

        if ($user === null) {
            http_response_code(400);
            echo json_encode([ 'message' => 'La petición la realizó un usuario no válido.' ]);
            die;
        }

        \Util\Mail::sendAccountRemovalEmail($user->userlogin, $user->fullname);

        $user->erase();

        echo json_encode([ 'message' => 'Usuario eliminado exitosamente.' ]);
    }

    public function mLookup()
    {
        $this->hasAuthOrDie();
        $user = $this->getUserData($this->auth->getUserId());
        $this->checkCurrentUserIdAdminOrDie($user);

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['email'], $params);

        $um = new \Data\UserModel();
        $user = $um->load(
            array( 'userlogin = :userlogin or phone = :userlogin', ':userlogin' => $params['email'] ),
            array( 'order' => ' id desc', 'limit' => 1 )
        );
        //$um->getUserByEmail($params['email']);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        if ($um->usertype != 1) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario no es de aplicación móvil.' ]);
            die;
        }

        echo json_encode($user->cast());
    }

    public function mRemove()
    {
        $this->hasAuthOrDie();
        $user = $this->getUserData($this->auth->getUserId());
        $this->checkCurrentUserIdAdminOrDie($user);

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['email'], $params);

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($params['email']);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario inexistente.' ]);
            die;
        }

        if ($um->usertype != 1) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario no es de aplicación móvil.' ]);
            die;
        }

        $um->erase();

        echo json_encode([ 'message' => 'Usuario eliminado exitosamente.' ]);
    }

    public function mUserCount()
    {

    }

    public function mUserList()
    {
        $this->hasAuthOrDie();
        $user = $this->getUserData($this->auth->getUserId());
        $this->checkCurrentUserIdAdminOrDie($user);
        
        if ($_GET['page'])
            $page = $_GET['page'];
        else
            $page = 1;

        $skip = ($page - 1) * 20;

        $busqueda = $_GET['busqueda'];
        $condi = "";
        if ($busqueda != '')
            $condi = " and fullname like '%" . $busqueda . "%' ";        

        $total_users = $this->f3->get('DB')->exec('select count(id) cnt from users where usertype=1' . $condi)[0]['cnt'];
        
        $total_pages = ceil($total_users / 20);
        $current_page = $page;

        $users = $this->f3->get('DB')->exec('
            select id, fullname, userlogin, phone, sentri_number, sentri_exp_date, user_pass_set, (select count(id) from vehicles where vehicles.id_user = users.id) tags 
            from users  
            where usertype=1 ' . $condi . ' limit 20 offset ?', $skip);
        
        echo json_encode([
            'users' => $users, 
            'total_pages' => intval($total_pages), 
            'current_page' => intval($current_page), 
            'total_users' => intval($total_users)
        ]);
    }

    public function mUserRemoveTags()
    {
        // does NOT remove, onli deactivates
        $this->hasAuthOrDie();
        // $this->checkPostParamsOrDie();

        $ru = $this->getUserData($this->auth->getUserId());
        if ($ru === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'La petición la realizó un usuario no válido.' ]);
            die;
        }
        $this->checkCurrentUserIdAdminOrDie($ru);

        $id = \intval($this->f3->get('PARAMS.id'));

        $this->f3->get('DB')->exec('delete from vehicles where id_user = ?', $id);

        echo json_encode([ 'message' => 'Tags eliminados exitosamente.' ]);

    }

}
