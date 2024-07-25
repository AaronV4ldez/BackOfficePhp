<?php


declare(strict_types=1);
require __DIR__ . "/bootstrap.php";

// ---------------------------------------- ff setup
$f3 = \Base::instance();
$f3->set('DEBUG', 3);

$conn_str = 'mysql:host=' . $_ENV["DB_HOST"] . ';port=3306;dbname=' . $_ENV["DB_NAME"] . ";charset=utf8";
$f3->set('DB', new DB\SQL($conn_str, $_ENV["DB_USER"], $_ENV["DB_PASS"]));

$f3->set('ONERROR', function ($f3) {
    $err = $f3->get('ERROR');
    if ($f3->get('DEBUG') == 0) {
        unset($err["trace"]);
    }
    $err["debug_level"] = $f3->get('DEBUG');
    ;
    echo json_encode($err);
    $f3->error($f3->get('ERROR.code'));
});


$res = \Util\LEApi::recargaSaldo('FPFC00022000', 1);
echo "result from recarga: ";
var_dump($res);
