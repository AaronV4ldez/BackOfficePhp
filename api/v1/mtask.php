<?php

// this file will be called from a cron task every MINUTE

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


function freeUnattendedUsers()
{
    $ptables = ['proc01','proc02','proc03','proc04','proc05'];

    $db = \Base::instance()->get('DB');
    
    foreach ($ptables as $ptable) {
        $db->exec("
            update $ptable 
            set cita_at_dt = null, id_user_cita = null 
            where 
              id_procedure_status = 5 
              and id_user_cita is not null 
              and TIMESTAMPDIFF(minute, cita_at_dt, current_timestamp()) >= 50
        ");
    }

    echo "freeUnattendedUsers() OK\n";
}

function removeUnfinishedPayments()
{
    $db = \Base::instance()->get('DB');
    $db->exec("delete from bntc where dt < DATE_SUB(current_timestamp(), INTERVAL 5 MINUTE)");

    echo "removeUnfinishedPayments() OK\n";
}

freeUnattendedUsers();
removeUnfinishedPayments();


