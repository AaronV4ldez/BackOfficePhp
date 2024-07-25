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
    !array_key_exists("email", $data) ||
    !array_key_exists("activation_code", $data)
) {
    http_response_code(400);
    echo json_encode(["message" => "Datos incompletos (Se requieren: correo electronico, codigo de activacion)."]);
    exit;
}

$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);
$user_gateway = new UserGateway($database);

$activation_result = $user_gateway->activateUser($data["email"], $data["activation_code"]);

if (!$activation_result) {
    http_response_code(401);
    echo json_encode(["message" => "Correo electronico o codigo invalido."]);
    exit;
}

echo json_encode(["message" => "Validacion de cuenta fue exitosa."]);
