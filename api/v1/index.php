<?php
declare(strict_types=1);
require __DIR__ . "/bootstrap.php";

//     if (isset($_SERVER['HTTP_ORIGIN'])) {
//         header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
//         header('Access-Control-Allow-Credentials: true');
//         header('Access-Control-Max-Age: 86400');    // cache for 1 day
//     }

//     // Access-Control headers are received during OPTIONS requests
//     if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

//         if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
//             header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

//         if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
//             header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

//         exit(0);
//     }

// ---------------------------------------- ff setup
$f3 = \Base::instance();
// $f3->set('CORS.origin', '*');
// $f3->copy('HEADERS.Origin','CORS.origin');
// header('Access-Control-Allow-Origin: *'); 
                
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
// header('Access-Control-Allow-Origin: *'); 

// echo "test1\n";
// $f3->route(
//     'GET /test/@id',
//     function ($f3) {
//         echo $f3->get('PARAMS.id');
//     }
// );
// echo "test2\n";
// $f3->run();
// echo "test3\n";
// return;
// echo "test4\n";


// ---------------------------------------- routes

$f3->route('POST /user/signup', 'Controllers\UserController->mobileUserSignup');
$f3->route('POST /user/newvcode', 'Controllers\UserController->requestNewVerificationCode');
$f3->route('POST /user/validate', 'Controllers\UserController->validateMobileUser'); //sms
$f3->route('GET /user/validate/@token', 'Controllers\UserController->validateMobileUserWithToken'); //email link
$f3->route('POST /user/changepass', 'Controllers\UserController->changeUserPassword');
$f3->route('POST /user/resetpass', 'Controllers\UserController->resetUserPassword');
$f3->route('POST /user/saveid', 'Controllers\UserController->saveDeviceID');
$f3->route('POST /user/sentri', 'Controllers\UserController->updateSentriData');
$f3->route('POST /user/fac', 'Controllers\UserController->updateInvoiceData');
$f3->route('POST /user/emailchangereq', 'Controllers\UserController->emailChangeRequest');
$f3->route('POST /user/getrtoken', 'Controllers\UserController->getResetToken');
$f3->route('POST /user/getetoken', 'Controllers\UserController->getEmailChangeToken');
$f3->route('POST /user/updatepass', 'Controllers\UserController->updatePass');
$f3->route('POST /user/updateemail', 'Controllers\UserController->updateEmail');
$f3->route('DELETE /user', 'Controllers\UserController->removeUser');
$f3->route('POST /user/mlookup', 'Controllers\UserController->mLookup');
$f3->route('POST /user/mremove', 'Controllers\UserController->mRemove');

$f3->route('GET /user/musers', 'Controllers\UserController->mUserList');
$f3->route('DELETE /user/musers/@id', 'Controllers\UserController->mUserRemoveTags');


// pw user admin
$f3->route('GET /user/pwusers', 'Controllers\UserController->getPanelWebUserList');
$f3->route('GET /user/pwusers/@id', 'Controllers\UserController->getPanelWebUser');
$f3->route('POST /user/pwusers', 'Controllers\UserController->createPanelWebUser');
$f3->route('PUT /user/pwusers/@id', 'Controllers\UserController->updatePanelWebUser');
$f3->route('DELETE /user/pwusers/@id', 'Controllers\UserController->removePanelWebUser');

// contacto
$f3->route('POST /contact', 'Controllers\ContactController->submit');

// login
$f3->route('POST /session/login', 'Controllers\SessionController->login');

$f3->route('GET /procs', 'Controllers\ProcController->list');

$f3->route('GET /files', 'Controllers\FileController->list');
$f3->route('POST /files', 'Controllers\FileController->upload');
$f3->route('GET /files/@fn', 'Controllers\FileController->getImage');
$f3->route('GET /doc/@fn', 'Controllers\FileController->getPDF');
$f3->route('POST /files/reject', 'Controllers\FileController->rejectFile');
$f3->route('POST /files/accept', 'Controllers\FileController->acceptFile');
$f3->route('GET /files/types', 'Controllers\FileController->getFileTypes');


$f3->route('POST /procs/p01', 'Controllers\Proc01Controller->create'); // create p01
$f3->route('GET /procs/p01/@id', 'Controllers\Proc01Controller->view'); // view p01
$f3->route('PUT /procs/p01/@id', 'Controllers\Proc01Controller->update'); // update p01
$f3->route('DELETE /procs/p01/@id', 'Controllers\Proc01Controller->delete'); // delete p01

$f3->route('POST /procs/p02', 'Controllers\Proc02Controller->create'); // create p02
$f3->route('GET /procs/p02/@id', 'Controllers\Proc02Controller->view'); // view p02
$f3->route('PUT /procs/p02/@id', 'Controllers\Proc02Controller->update'); // update p02
$f3->route('DELETE /procs/p02/@id', 'Controllers\Proc02Controller->delete'); // delete p02

$f3->route('POST /procs/p03', 'Controllers\Proc03Controller->create'); // create p03
$f3->route('GET /procs/p03/@id', 'Controllers\Proc03Controller->view'); // view p03
$f3->route('PUT /procs/p03/@id', 'Controllers\Proc03Controller->update'); // update p03
$f3->route('DELETE /procs/p03/@id', 'Controllers\Proc03Controller->delete'); // delete p03

$f3->route('POST /procs/p04', 'Controllers\Proc04Controller->create'); // create p04
$f3->route('GET /procs/p04/@id', 'Controllers\Proc04Controller->view'); // view p04
$f3->route('PUT /procs/p04/@id', 'Controllers\Proc04Controller->update'); // update p04
$f3->route('DELETE /procs/p04/@id', 'Controllers\Proc04Controller->delete'); // delete p04

$f3->route('POST /procs/p05', 'Controllers\Proc05Controller->create'); // create p05
$f3->route('GET /procs/p05/@id', 'Controllers\Proc05Controller->view'); // view p05
$f3->route('PUT /procs/p05/@id', 'Controllers\Proc05Controller->update'); // update p05
$f3->route('DELETE /procs/p05/@id', 'Controllers\Proc05Controller->delete'); // delete p05

$f3->route('GET /operator/queue', 'Controllers\FilaController->queue');
$f3->route('GET /operator/scheduled', 'Controllers\FilaController->scheduled');
$f3->route('GET /operator/wip', 'Controllers\FilaController->wip');
$f3->route('GET /operator/done', 'Controllers\FilaController->done');
$f3->route('GET /operator/reviewproc/@idp/@id', 'Controllers\ProcController->view');
$f3->route('POST /operator/assignproc', 'Controllers\ProcController->assignProc');
$f3->route('POST /operator/assigncita', 'Controllers\ProcController->assignCita');
$f3->route('POST /operator/cancelproc', 'Controllers\ProcController->cancelProc');

$f3->route('POST /appointments/available', 'Controllers\AppointmentsController->availableByDate');
$f3->route('POST /appointments/create', 'Controllers\AppointmentsController->createAppointment');
$f3->route('POST /appointments/change', 'Controllers\AppointmentsController->changeAppointmentDate');
$f3->route('GET /appointments', 'Controllers\AppointmentsController->getAppointmentList');
$f3->route('GET /appointments/@id', 'Controllers\AppointmentsController->getAppointmentDetail');
$f3->route('POST /appointments/cancel', 'Controllers\AppointmentsController->cancelAppointment');
$f3->route('POST /appointments/cancelproc', 'Controllers\AppointmentsController->cancelProc');
$f3->route('POST /appointments/finish', 'Controllers\AppointmentsController->finishAppointment');


// vehicles
$f3->route('GET /vehicles', 'Controllers\VehiclesController->list');
$f3->route('GET /vehicles/@id', 'Controllers\VehiclesController->details');
$f3->route('POST /vehicles', 'Controllers\VehiclesController->create');
$f3->route('PUT /vehicles/@id', 'Controllers\VehiclesController->update');
$f3->route('DELETE /vehicles/@id', 'Controllers\VehiclesController->delete');
$f3->route('POST /vehicles/refresh', 'Controllers\VehiclesController->refresh');
$f3->route('POST /vehicles/leload', 'Controllers\VehiclesController->getSentriVehicles');

// payments
$f3->route('POST /payments/processpayment', 'Controllers\PaymentsController->processPayment');
$f3->route('POST /payments/afform', 'Controllers\PaymentsController->getAutoFillData');
$f3->route('POST /payments/afsave', 'Controllers\PaymentsController->saveAutoFillData');

$f3->route('POST /tags/isvalid', 'Controllers\TagsController->tagIsValid');
$f3->route('GET /tags/exists/@tag', 'Controllers\TagsController->tagExists');
$f3->route('GET /tags/gfs/@sentri/@email/@placa', 'Controllers\TagsController->gfs');

$f3->route('POST /tags/ispayable', 'Controllers\PaymentsController->checkTagPayable');

$f3->route('GET /config', 'Controllers\ConfigController->getConfig');
$f3->route('POST /config', 'Controllers\ConfigController->setConfig');
$f3->route('GET /config/mobile', 'Controllers\ConfigController->mobileConfig');

// dash
$f3->route('POST /dash/payments', 'Controllers\DashController->paymentData');
$f3->route('POST /dash/paymentsDet', 'Controllers\DashController->paymentDetail');

$f3->route('POST /dash/finishedSum', 'Controllers\DashController->completedProceduresSummary');
$f3->route('POST /dash/finishedDet', 'Controllers\DashController->completedProceduresDetail');
$f3->route('POST /dash/cancelledSum', 'Controllers\DashController->cancelledProceduresSummary');
$f3->route('POST /dash/cancelledDet', 'Controllers\DashController->cancelledProceduresDetail');
$f3->route('POST /dash/wipSum', 'Controllers\DashController->wipProceduresSummary');
$f3->route('POST /dash/wipDet', 'Controllers\DashController->wipProceduresDetail');

$f3->route('POST /search', 'Controllers\SearchController->search');
$f3->route('POST /searchld', 'Controllers\LDataController->search');

// TP
$f3->route('GET /tp/balance/@tag', 'Controllers\TPController->consultaSaldo');
$f3->route('POST /tp/recarga', 'Controllers\TPController->recargaSaldo');


//  LE
$f3->route('GET /le/user/@sentri/@email', 'Controllers\LEController->getUserInfo');
$f3->route('GET /le/user/@id/vehicles', 'Controllers\LEController->getUserCars');
$f3->route('GET /le/crossings/@idu/@idv', 'Controllers\LEController->getCarCrossings');
//nuevo api de cruces
$f3->route('GET /le/crossingsnew/@tag','Controllers\LEController->getCarCrossingsnew');

// stripe
$f3->route('POST /stripe/createpi', 'Controllers\StripeController->createIntent');
$f3->route('GET /stripe/confirm/@ref', 'Controllers\StripeController->confirmPayment');
$f3->route('GET /stripe/details/@ref', 'Controllers\StripeController->paymentDetails');

// me carga el payaso climatico
$f3->route('GET /clima', 'Controllers\ClimaController->clima');

$f3->route(
    'GET /test/@id',
    function ($f3) {
        // $a = new \Data\Proc01Model;
        // $b = $a->loadByID(1);
        // echo "la b -------------------------\n";
        // print_r($b);
        // echo "-------------------------\n";
        // var_dump($b->cast());
        // echo "la a cast-------------------------\n";
        // print_r($a->cast());

        // $id = $f3->get('PARAMS.id');
        // echo "-------------------------\n";
        // print_r($id);
        // echo "-------------------------\n";
        // var_dump($id);
        // echo "-------------------------\n";
        // var_dump($_);
        echo $f3->get('PARAMS.id');
    }
);

// eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEzLCJuYW1lIjoiT21hciBPcmRheiIsImV4cCI6MTY1OTA3NjA1NX0.agz8dxjOGVrkUaazwaF4B2Ig3kJcOMGXsshrUJKLKts

$f3->run();
