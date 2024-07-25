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
    !array_key_exists("userlogin", $data) ||
    !array_key_exists("password", $data)
) {

    http_response_code(400);
    echo json_encode(["message" => "No se recibieron datos de usuario." . implode(" ", $data)]);
    exit;
}

$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);
$user_gateway = new UserGateway($database);
$user = $user_gateway->getByUserName($data["userlogin"]);

if ($user === false) {
    http_response_code(401);
    echo json_encode(["message" => "Credenciales invalidas."]);
    exit;
}

if (!password_verify($data["password"], $user["password_hash"])) {
    http_response_code(401);
    echo json_encode(["message" => "Credenciales invalidas."]);
    exit;
}

if ($user["account_verified"] !== 1) {
    http_response_code(401);
    echo json_encode(["message" => "Cuenta sin verificar. Si no recibio SMS de activacion, solicite uno mediante la App."]);
    exit;
}

$payload = [
    "sub" => $user["id"],
    "name" => $user["fullname"],
    "exp" => time() + 20
];

$codec = new JWTCodec($_ENV["SECRET_KEY"]);
$access_token = $codec->encode($payload);

echo json_encode(["access_token" => $access_token]);
