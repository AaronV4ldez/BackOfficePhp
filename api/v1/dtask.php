<?php

// this file will be called from a cron task every day at 3:00 AM

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


function sendSentriExpirationEmails()
{
    $db = \Base::instance()->get('DB');
    $eu = $db->exec("
        select userlogin, fullname, sentri_number, sentri_exp_date 
        from users 
        where date(sentri_exp_date) = date_add(date(current_timestamp), interval 30 day);
    ");

    foreach ($eu as $user) {
        \Util\Mail::sendSentriExpirationEmail(
            $user["userlogin"],
            $user["fullname"],
            $user["sentri_number"],
            date_format(date_create($user["sentri_exp_date"]), 'Y-m-d')
        );
    }

    echo "sendSentriExpirationEmails() OK\n";
}

function sendContractExpirationEmails()
{
    $db = \Base::instance()->get('DB');
    $ea = $db->exec("select * from vw_contratos_vence where dias_vence in (30, 14, 7)");

    foreach ($ea as $contract) {
        \Util\Mail::sendContractExpirationEmail(
            $contract["user_email"],
            $contract["user_name"],
            $contract["marca"] . " " . $contract["linea"] . " " . $contract["modelo"] . " con placa " . $contract["placa"],
            $contract["clt_expiration_date"]
        );
    }

    echo "sendContractExpirationEmails() OK\n";
}

function sendExpiredAppointmentsEmails()
{
    $db = \Base::instance()->get('DB');
    $ea = $db->exec("select * from vw_appointments where dt < current_timestamp and dt >= date_add(current_timestamp, interval -5 day)");
    foreach ($ea as $appt) {
        \Util\Mail::sendExpiredAppointmentEmail(
            $appt["usuario_email"],
            $appt["usuario_nombre"],
            $appt["tramite"],
            date_format(date_create($appt["dt"]), 'Y-m-d'),
            date_format(date_create($appt["dt"]), 'H:i')
        );
    }
    echo "sendExpiredAppointmentsEmails() OK\n";
}

sendContractExpirationEmails();
sendSentriExpirationEmails();
sendExpiredAppointmentsEmails();
