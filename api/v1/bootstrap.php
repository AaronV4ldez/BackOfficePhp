<?php
ini_set("display_errors", "On");

require __DIR__ . "/vendor/autoload.php";

// set_error_handler("ErrorHandler::handleError");
// set_exception_handler("ErrorHandler::handleException");

$dotenv = Dotenv\Dotenv::createImmutable('/var/www/liexapiconf/v1');
$dotenv->load();

header("Content-type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, Authorization");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");

// if (
//   isset( $_SERVER['REQUEST_METHOD'] )
//   && $_SERVER['REQUEST_METHOD'] === 'OPTIONS'
// ) {
//   header( 'Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept' );
//   header( 'Access-Control-Max-Age: 86400' );
//   header( 'Cache-Control: public, max-age=86300' );
//   header('Access-Control-Allow-Origin: *');
//   header( 'Vary: origin' );
//   exit( 0 );
// }


// header('Access-Control-Allow-Origin: *');
// header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
// header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
// header('Content-Type: application/json');
// $method = $_SERVER['REQUEST_METHOD'];
// if ($method == "OPTIONS") {
// header('Access-Control-Allow-Origin: *');
// header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
// header("HTTP/1.1 200 OK");
// die();
// }