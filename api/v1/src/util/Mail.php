<?php

// Mail.php
// Funciones para envio de correos de los diferentes eventos del sistema
// LineaExpressApp

namespace Util;

class Mail
{
    public const avoidMessageSentences = ['inició sesión'];

    public static function fechaLarga($fecha)
    {
        setlocale(LC_ALL, "es_ES.UTF-8");
        $fechastr =  \date_format(date_create($fecha), 'Y-m-d');
        // $fecha = strftime("%A %d de %B de %Y", strtotime($fechastr));
    	$formatter = new \IntlDateFormatter('es_ES', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
   		$fecha = $formatter->format(strtotime($fechastr)); 
        return ucwords($fecha);
    }

    public static function getAppName(): string
    {
        return \urldecode($_ENV['APP_NAME']);
    }

    public static function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $textBody,
        string $htmlBody,
        array $fields = []
    ): void {
        $apiKeyMJ = $_ENV['MAILJET_API_KEY'];
        $secretKeyMJ = $_ENV['MAILJET_SECRET_KEY'];

        $textBody = str_replace(array_keys($fields), array_values($fields), $textBody);
        $htmlBody = str_replace(array_keys($fields), array_values($fields), $htmlBody);

        try {
            $user = new \Data\UserModel();
            $user->load(['userlogin=?', $toEmail]);
            if (!$user->dry()) {
                $avoidMessage = false;
                foreach (self::avoidMessageSentences as $phrase) {
                    if (strpos($textBody, $phrase) !== false) {
                        $avoidMessage = true;
                        break;
                    }
                }

                if (!$avoidMessage) {
                    \SMSApi::sendWhatsappMessage($user->phone, $textBody, $user->country_code);
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $mj = new \Mailjet\Client($apiKeyMJ, $secretKeyMJ, true, ['version' => 'v3.1']);
            $body = [
                'Messages' => [
                    [
                        'From' => [
                            // anterior email posible error
                            'Email' => 'sistema@lineaexpress.desarrollosenlanube.net', //lineaexpressapp@gmail.com
                            //'Email' => 'sistema@lineaexpress.fpfch.gob', //lineaexpressapp@gmail.com
                            'Name' => 'LineaExpresApp',
                        ],
                        'To' => [
                            [
                                'Email' => $toEmail,
                                'Name' => $toName,
                            ],
                        ],
                        'Subject' => $subject,
                        'TextPart' => $textBody,
                        'HTMLPart' => $htmlBody,
                        'CustomID' => '',
                    ],
                ],
            ];
            $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
            \error_log('liex mail: ' . var_export($response->getData(), true));

        } catch (\Throwable $th) {
            \error_log('liex mail error: ' . var_export($th->getMessage(), true));
        }
    }

    public static function sendPaymentErrorEmail($toEmail, $toName, $details)
    {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = 'Parece que hubo un error al procesar un pago.\n\n\n' . $details;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;
        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Error al procesar pago en ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendLoginEmail(
        string $toEmail,
        string $toName
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $dt = date('d/m/Y H:i:s');

        $content = 'Parece que el ' . $dt . ', usted inició sesión en su cuenta de ' . self::getAppName() . '. Si no inició sesión en su cuenta de ' . self::getAppName() . ', por favor contáctenos inmediatamente';

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;
        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);

        self::sendEmail($toEmail, $toName, 'Inicio de sesion en ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendVerificationEmail(
        string $toEmail,
        string $toName,
        string $token
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $link = 'https://apis.fpfch.gob.mx/api/v1/user/validate/' . $token;

        $content = 'Por favor haga click en el siguiente link para activar su cuenta de ' . self::getAppName() . ': \n\n' . $link;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $htmlContent = "Por favor haga click <a href='" . $link . "'>AQUI</a> para activar su cuenta de " . self::getAppName() . "."
            . '<br /><br />Si no puede hacer click en el link, copie y pegue el siguiente en su navegador: <br /><br />' . $link;

        $arr = ['__fullname__' => $toName, '__content__' => $htmlContent];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);

        self::sendEmail($toEmail, $toName, 'Active su cuenta de ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendRejectedFileNotification(
        string $toEmail,
        string $toName,
        string $fileName,
        string $reason
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = 'Parece que el archivo que contiene su '
        . \trim($fileName)
            . ' no cumple con los requerimientos del tramite (motivo de rechazo: '
            . $reason . '). Por favor, intente subir el archivo nuevamente.';

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;
        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        self::sendEmail($toEmail, $toName, 'Archivo no cumple con las especificaciones de ' . self::getAppName(), $textBody, $htmlBody);
    }
    /* cambio random password
    public static function sendRandomPassword(
        string $toEmail,
        string $toName,
        string $password
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = 'Su contraseña temporal es: ' . $password
            . ' Utilicela para ingresar a la aplicacion y luego cambiela por una de su preferencia.';

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;
        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        self::sendEmail($toEmail, $toName, 'Su contraseña temporal de ' . self::getAppName(), $textBody, $htmlBody);
    }*/

    public static function sendPreApprovedNotification(
        string $toEmail,
        string $toName,
        string $procName,
        bool $con_cita = true
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = 'Su tramite: ' . $procName . ' ha sido pre-aprobado.';
        if ($con_cita) {
            $content .= ' Ingrese a la aplicacion y seleccione una fecha de su conveniencia para agendar una cita.';
        }

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;
        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        self::sendEmail($toEmail, $toName, 'Su tramite en ' . self::getAppName() . ' se ha pre-aprobado', $textBody, $htmlBody);
    }   

    public static function sendApprovedNotification(
        string $toEmail,
        string $toName,
        string $procName,
        bool $con_cita = true
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = 'Su tramite: ' . $procName . ' ha sido aprobado.';
        if ($con_cita) {
            $content .= ' Ingrese a la aplicacion y seleccione una fecha de su conveniencia para agendar una cita.';
        }

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;
        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        self::sendEmail($toEmail, $toName, 'Su tramite en ' . self::getAppName() . ' se ha aprobado', $textBody, $htmlBody);
    }

    public static function sendPasswordResetCodeEmail(
        string $toEmail,
        string $toName,
        string $code
    ): void {
        $content = 'El codigo para restablecer su contraseña de ' . \Util\Mail::getAppName() . ' es: ' . $code .  "\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content . $link;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Codigo para restablecer su contraseña de ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendPasswordResetEmail(
        string $toEmail,
        string $toName,
        string $token
    ): void {
        //Link original, detectar posible error
        // $link = 'https://lineaexpressapp.desarrollosenlanube.net/#/resetpass/' . $token;
        $link = 'https://fpfch.gob.mx/#/resetpass/' . $token;
        // $link = 'http://localhost:3000/#/resetpass/' . $token;

        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Recibimos una solicitud para cambiar su contraseña de " . self::getAppName() . ", " .
            "si usted no inicio esta solicitud. Por favor haga caso omiso de este correo; " .
            "de lo contrario haga click en el siguiente link para restablecer su contraseña de " . self::getAppName() . ": \n\n\n" .
            "Se le enviara un codigo de verificación por SMS, este SMS puede demorar unos minutos en llegar." .  "\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content . $link;

        $arr = ['__fullname__' => $toName, '__content__' => $content . "<br/><br/><a href='" . $link . "'>Haga click aqui para restablecer su contraseña</a><br/><br/>O copie y pegue el siguiente enlace en su navegador: <br/><br/>" . $link];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Restablecer su contraseña de ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendEmailChangeEmail(
        string $toEmail,
        string $toName,
        string $token
    ): void {
        //Link original, posible error o cambio de dirección
        // $link = 'https://lineaexpressapp.desarrollosenlanube.net/#/changeemail/' . $token;
        $link = 'https://fpfch.gob.mx/#/changeemail/' . $token;
        // $link = 'http://localhost:3000/#/resetpass/' . $token;

        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Recibimos una solicitud para actualizar su correo electronico en " . self::getAppName() . ", " .
            "si usted no inicio esta solicitud. Por favor haga caso omiso de este correo; " .
            "de lo contrario haga click en el siguiente link para actualizar su correo de " . self::getAppName() . ": \n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content . $link;

        $arr = ['__fullname__' => $toName, '__content__' => $content . "<br/><br/><a href='" . $link . "'>Haga click aqui para actualizar su correo</a><br/><br/>O copie y pegue el siguiente enlace en su navegador: <br/><br/>" . $link];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Actualizar su correo de ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendPassResetSuccessEmail(
        string $toEmail,
        string $toName
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Su contraseña fue cambiada exitosamente. \n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content ];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Cambio de contraseña de ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendEmailChangeSuccessEmail(
        string $toEmail,
        string $toName
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Su correo fue actualizado exitosamente. \n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content ];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Cambio de correo de ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendNewProcEmail(
        string $toEmail,
        string $toName,
        string $procName
    ): void {

        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Recibimos su solicitud de trámite para " . $procName . ", " .
            "uno de nuestros operadores revisara su documentacion y dará seguimiento a su solicitud.\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/>", $htmlBody);

        //self::sendEmail($toEmail, $toName, 'Recibimos su solicitud de trámite de ' . self::getAppName(), $textBody, $htmlBody);
    }


    public static function sendApptCancelEmail(
        string $toEmail,
        string $toName,
        string $procName,
        string $comment
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Su cita para el trámite " . \strtoupper($procName) . ", " .
            "ha sido cancelada por motivo (" . $comment . "). Es necesario que seleccione una nueva fecha y hora desde la App para agendar una nueva cita.\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Cita cancelada. ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendProcCancelEmail(
        string $toEmail,
        string $toName,
        string $procName,
        string $comment
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Su trámite " . $procName . ", " .
            "ha sido cancelado por motivo (" . $comment . "). Podra reiniciar el tramite desde la App si así lo desea.\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Trámite cancelado. ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendProcFinishEmail(
        string $toEmail,
        string $toName,
        string $procName,
        string $comment
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Su trámite " . $procName . ", " .
            "ha finalizado exitosamente.\n\n\n Comentarios de su tramitador: " . $comment . "\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Trámite finalizado. ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendNewApptEmail(
        string $toEmail,
        string $toName,
        string $procName,
        string $date,
        string $time
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        // $fechax = \DateTime("$date $time");
        $fechastr =  self::fechaLarga($date); // date_format(date_create($date), 'd/m/Y');

        $content = "Confirmación de cita para su trámite " . strtoupper($procName) . ", " .
            "a las $time horas el dia $fechastr. Valoramos su tiempo favor de estar 5 minutos antes de la cita. Gracias!!\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Confirmación de cita. ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendExpiredAppointmentEmail(
        string $toEmail,
        string $toName,
        string $procName,
        string $date,
        string $time
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $fechastr =  self::fechaLarga($date);

        $content = "Su cita para el trámite " . strtoupper($procName) . " para " .
            "fecha: $fechastr, hora: $time no pudo ser atendida. Por favor cambie su cita para una fecha posterior desde la aplicación.\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Cita expirada. ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendSentriExpirationEmail(
        string $toEmail,
        string $toName,
        string $sentri_num,
        string $sentri_ed
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $fechastr =  self::fechaLarga($sentri_ed);

        $content = "Su SENTRI " . $sentri_num . " tiene fecha de expiración " .
            "$fechastr.  Le recomendamos acudir al CBP para iniciar su proceso de renovación.\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'SENTRI próxima a vencer. ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendContractExpirationEmail(
        string $toEmail,
        string $toName,
        string $car_data,
        string $exp_date
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $fechastr =  self::fechaLarga($exp_date);

        $content = "El contrato de su " . $car_data . " tiene fecha de expiración " .
            "$fechastr.  Le recomendamos iniciar un tramite de renovación de contrato.\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Contrato próximo a vencer. ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendContactEmail(
        string $toEmail,
        string $toName,
        string $nom,
        string $ap,
        string $email,
        string $tel,
        string $mensaje
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $fechastr =  self::fechaLarga($exp_date);

        $content = "El usuario " . $nom . " " . $ap . " ha enviado el siguiente mensaje desde la forma de contacto de la App: \n\n\n $mensaje \n\n\n" .
            "para dar seguimiento, puede contactar al usuario en el correo: $email o al teléfono: $tel\n\n\n " ;

        $textBody = $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Mensaje desde forma de contacto. ' . self::getAppName(), $textBody, $htmlBody);
    }

    public static function sendAccountRemovalEmail(
        string $toEmail,
        string $toName
    ): void {
        $htmlc = file_get_contents(__DIR__ . '/emailtemplate.html');

        $content = "Su cuenta de " . self::getAppName() . " ha sido eliminada.\n\n\n" .
            "Si contaba con saldo a favor en sus tags o vehiculos, este no se verá afectado.\n\n\n" ;

        $textBody = 'Estimado/a ' . $toName . '. ' . $content;

        $arr = ['__fullname__' => $toName, '__content__' => $content];
        $htmlBody = str_replace(array_keys($arr), array_values($arr), $htmlc);
        $htmlBody = str_replace("\n\n\n", "<br/><br/><br/>", $htmlBody);

        self::sendEmail($toEmail, $toName, 'Cuenta eliminada. ' . self::getAppName(), $textBody, $htmlBody);
    }

}
