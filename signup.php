<?php

declare(strict_types=1);
require dirname(__DIR__) . "/html_liex/bootstrap.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit;
}

$data = (array) json_decode(file_get_contents("php://input"));

if (
    !array_key_exists("fullname", $data) ||
    !array_key_exists("email", $data) ||
    !array_key_exists("phone", $data) ||
    !array_key_exists("password", $data)
) {
    http_response_code(400);
    echo json_encode(["message" => "Datos incompletos (Se requieren: nombre, correo electronico, password, telefono)."]);
    exit;
}

if (strlen($data["password"]) < 8) {
    http_response_code(400);
    echo json_encode(["message" => "Password debe ser de 8 (o mas) caracteres."]);
    exit;
}

if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["message" => "Direccion de correo electrónico no está en formato correcto."]);
    exit;
}

$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);
$user_gateway = new UserGateway($database);
$user = $user_gateway->getByUserName($data["email"]);

if ($user !== false) {
    http_response_code(400);
    echo json_encode(["message" => "Usuario ya existe."]);
    exit;
}

$user_id = $user_gateway->createUser($data["fullname"], $data["email"], $data["password"], $data["phone"]);
$smscode = $user_gateway->createUserActivationRecord($user_id);

// use api to request activation sms
$smsapi = new SMSApi;
$sms_result = $smsapi->sendSMS($data["phone"], "El codigo para activar su cuenta de LineaExpressApp es: " . $smscode);

$smsapi = new SMSApi;
$sms_result = $smsapi->sendSMSusa($data["phone"], "El codigo para activar su cuenta de LineaExpressApp es: " . $smscode);


echo json_encode(["message" => "Usuario registrado exitosamente.", "sms" => $sms_result]);

