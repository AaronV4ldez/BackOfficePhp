<?php

// SessionController.php
// Manejo de sesiones de usuarios
// LineaExpressApp

namespace Controllers;

class SessionController extends BaseController
{
    public function login()
    {
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        if (
            !array_key_exists("userlogin", $params) ||
            !array_key_exists("password", $params)
        ) {
            http_response_code(400);
            echo json_encode(["message" => "No se recibieron credenciales de usuario."]);
            die;
        }

        $um = new \Data\UserModel();
        $user = $um->getUserByEmail($params["userlogin"]);
        if ($user === false) {
            http_response_code(400);
            echo json_encode(["message" => "Credenciales invalidas."]);
            die;
        }

        if (!password_verify($params["password"], $user->password_hash)) {
            http_response_code(401);
            echo json_encode(["message" => "Credenciales invalidas."]);
            die;
        }

        if ($user->account_verified !== 1) {
            http_response_code(401);
            echo json_encode(["message" => "Cuenta sin verificar. Si no recibio SMS de activacion, solicite uno mediante la App."]);
            exit;
        }

        if ($user->is_active !== 1) {
            http_response_code(401);
            echo json_encode(["message" => "Cuenta desactivada. Reporte este problema a su administrador."]);
            exit;
        }

        $payload = [
            "sub" => $user["id"],
            "name" => $user["fullname"],
            "exp" => time() + 20
        ];

        $codec = new \JWTCodec($_ENV["SECRET_KEY"]);
        $access_token = $codec->encode($payload);

        // send email only to mobile app users
        if ($user->usertype === 1) {
            \Util\Mail::sendLoginEmail($user->userlogin, $user->fullname);
        }

        // get local date on iso format
        $local_date = date("Y-m-d", time());

        echo json_encode([
            "access_token" => $access_token,
            "name" => $user["fullname"],
            "user_set_pwd" => $user["user_pass_set"],
            "id" => $user["id"],
            "ut" => $user["usertype"],
            "sentri" => $user["sentri_number"],
            "sentri_exp_date" => $user["sentri_exp_date"] ? $user["sentri_exp_date"] : $local_date,
            //invoice data
            "fac_razon_social" => $user["fac_razon_social"],
            "fac_rfc" => $user["fac_rfc"],
            "fac_dom_fiscal" => $user["fac_dom_fiscal"],
            "fac_email" => $user["fac_email"],
            "fac_telefono" => $user["fac_telefono"],
            "fac_cp" => $user["fac_cp"],
            "sms_validated" => $user["sms_validated"],
        ]);
    }
}
