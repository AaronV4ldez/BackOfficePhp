<?php

ini_set("display_errors", "On");

require dirname(__DIR__) . "/html_liex/vendor/autoload.php";

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__)); // . "/html_liex/"
$dotenv->load();

header("Content-type: application/json; charset=UTF-8");

