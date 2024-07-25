<?php

declare(strict_types=1);
require dirname(__DIR__) . "/html_liex/bootstrap.php";

// break if no autorization token is received
$codec = new JWTCodec($_ENV["SECRET_KEY"]);
$auth = new Auth($codec);
if (!$auth->authenticateAccessToken()) {
    die;
}

// setup database connection and user controller
$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);
$user_controller = new UserController($database, $auth->getUserId());

$user_controller->checkParameters();
$user_controller->processRequest();