<?php

declare(strict_types=1);
require __DIR__ . "/bootstrap.php";

// \Util\Mail::sendMJ(
//     'omar@omarordaz.com',
//     'omar',
//     'test de mj',
//     'cuerpo generico texto',
//     '<h1>cuerpo genetico html</h1',
//     []
// );

// \Util\Mail::sendLoginEmail('omar@omarordaz.com', 'omar');

// \Util\Mail::sendVerificationEmail('omar@omarordaz.com', 'el omar', 'e38c561c8799fd8d1b79f782774f4ae7');

// \Util\Mail::verif();

// echo "sending mail using local SMTP server...\n\n";
// \Util\Mail::sendEmail(
//     'perro1@jesusordaz.com',
//     'omar',
//     'test de mj',
//     'cuerpo generico texto',
//     '<h1>cuerpo generico html</h1>');

// \Util\Mail::sendRandomPassword('joordaz@hotmail.com', 'omar', '123456');

// \Util\Mail::sendLoginEmail('joordaz@hotmail.com', 'omar');

// \Util\Mail::sendVerificationEmail('joordaz@hotmail.com', 'omar', 'e38c561c8799fd8d1b79f782774f4ae7');

//\Util\Mail::sendMail('joordaz@hotmail.com', 'el oamr', 'correo de prueba', '', '<h1>hola</h1>', []);


// $ft = new \Data\FileTypeModel;
// $validFileTypes = $ft->loadAllRecordsToArray();

echo $_ENV["DB_HOST"] . "\n";
echo $_ENV["DB_USER"] . "\n";
echo $_ENV["DB_PASS"] . "\n";

$f3 = \Base::instance();
$f3->set('DEBUG', 3);

$conn_str = 'mysql:host=' . $_ENV["DB_HOST"] . ';port=3306;dbname=' . $_ENV["DB_NAME"];
$f3->set('DB', new DB\SQL($conn_str, $_ENV["DB_USER"], $_ENV["DB_PASS"]));



// $f3->set('ONERROR', function ($f3) {
//     $err = $f3->get('ERROR');
//     if ($f3->get('DEBUG') == 0) {
//         unset($err["trace"]);
//     }
//     $err["debug_level"] = $f3->get('DEBUG');
//     ;
//     echo json_encode($err);
//     $f3->error($f3->get('ERROR.code'));
// });

// $ft = new \Data\FileTypeModel();
// $validFileTypes = $ft->loadIDsToArray();


// var_dump($validFileTypes);


// use PhpOffice\PhpSpreadsheet\Spreadsheet;

// $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
// try {
//     $spreadsheet = $reader->load("/var/www/tags.xlsx");
// } catch (Exception $e) {
//     echo  "Error: Archivo invalido, no hay hojas en el archivo";
//     return;
// }

// // $spreadsheet = $reader->load("/var/www/tags.xlsx");

// // var_dump($spreadsheet);
// // count sheets on the file
// echo "hojas: ", $spreadsheet->getSheetCount(), "\n";
// if ($spreadsheet->getSheetCount() == 0) {
//     echo  "Error: Archivo invalido, no hay hojas en el archivo";
// }

// $d=$spreadsheet->getSheet(0)->toArray();

// // var_dump($d);
// if (trim(strtoupper($d[0][0])) !== "FOLIO") {
//     echo "error, no folio column";
//     exit;
// }
// if (trim(strtoupper($d[0][1])) !== "TID") {
//     echo "error, no folio column";
//     exit;
// }
// if (trim(strtoupper($d[0][2])) !== "EPC") {
//     echo "error, no folio column";
//     exit;
// }

// for ($rowNo = 1; $rowNo < count($d); $rowNo++) {
//     $row = $d[$rowNo];
//     $folio = $row[0];
//     $tid = $row[1];
//     $epc = $row[2];
//     echo "Folio: $folio, TID: $tid, EPC: $epc\n";
// }



// -- PDF begin
// $f3 = \Base::instance();
// $f3->set('DEBUG', 3);

// $conn_str = 'mysql:host=' . $_ENV["DB_HOST"] . ';port=3306;dbname=' . $_ENV["DB_NAME"];
// $f3->set('DB', new DB\SQL($conn_str, $_ENV["DB_USER"], $_ENV["DB_PASS"]));

// $f3->set('ONERROR', function ($f3) {
//     $err = $f3->get('ERROR');
//     if ($f3->get('DEBUG') == 0) {
//         unset($err["trace"]);
//     }
//     $err["debug_level"] = $f3->get('DEBUG');
//     ;
//     echo json_encode($err);
//     $f3->error($f3->get('ERROR.code'));
// });

// $id = 94;
// $idp = 1;

// $data = $f3->get('DB')->exec("select file_name, file_type_desc, status from vw_file_info where id_proc= {$id} and id_procedure= {$idp} order by id_file_type");
// $descs=[];
// $paths=[];
// foreach ($data as $row) {
//     $descs[]=$row['file_type_desc'];
//     $paths[]=$row['file_name'];
// }
// switch ($idp) {
//     case 1:
//         $proc = new \Data\VUserProc01Model();
//         break;
//     case 2:
//         $proc = new \Data\VUserProc02Model();
//         break;
//     case 3:
//         $proc = new \Data\VUserProc03Model();
//         break;
//     case 4:
//         $proc = new \Data\VUserProc04Model();
//         break;
//     case 5:
//         $proc = new \Data\VUserProc05Model();
//         break;
//     case 6:
//         $proc = new \Data\VUserProc06Model();
//         break;
//     default:
//         $this->respondUnprocessableEntity($id);
//         break;
// }
// $lr = $proc->loadByID($id);
// if ($proc->dry()) {
//     echo "no hay registro";
//     return;
// }
// $lr = $lr->cast(); 
// \Util\PDF::createPDF2($lr, $descs, $paths, '/tmp/omar.pdf');
// -- PDF end

// \Util\Mail::sendNewApptEmail('omar@omarordaz.com', 'omar', 'tramite de prueba', '2023-03-21', '10:00');

// \Util\Notify::sendFirebaseMessage('Prueba mensajera desde app a ver que pex!', 'fQipGfYdmUaJhwzmD1iJGf:APA91bE1AQQpsiyD-1NKkLvt8gpNpCU9SxU8E3CvKxIq_sU3WJBO32Gm4VIfeCrnsRzGVBFM7h79cJG8HRZQ1HKBM6iEEiO_Mf9DlDU98AvNwjfWkdLvF5kFg6qmObvdrhTmJrhpyLwL');


// function payworks_call($p_amount, $p_cn, $p_fe, $p_sc, $p_eci, $p_cavv, $p_ref3d, $p_xid)
// {
//     $pw_url = $_ENV["PW_URL"];
//     $pw_merchant = $_ENV["PW_MERCHANT"];
//     $pw_user = $_ENV["PW_USER"];
//     $pw_pass = $_ENV["PW_PASS"];
//     $pw_terminal = $_ENV["PW_TERMINAL"];
//     $pw_mode = $_ENV["PW_MODE"];

//     $postdata = "MERCHANT_ID=$pw_merchant&USER=$pw_user&PASSWORD=$pw_pass&CMD_TRANS=AUTH";
//     $postdata .= "&TERMINAL_ID=$pw_terminal&AMOUNT=$p_amount&MODE=$pw_mode&CARD_NUMBER=$p_cn";
//     $postdata .= "&CARD_EXP=$p_fe&SECURITY_CODE=$p_sc&ENTRY_MODE=MANUAL&RESPONSE_LANGUAGE=EN";
//     $postdata .= "&ESTATUS_3D=200&ECI=$p_eci&CAVV=$p_cavv&VERSION_3D=2&REFERENCIA3D=$p_ref3d&XID=$p_xid";

//     \Util\Logger::log("payworks_request: " . $postdata);
//     error_log("payworks_request: " . $postdata);

//     $headers = [];
//     $ch = curl_init();

//     curl_setopt_array($ch, [
//         CURLOPT_URL => $pw_url,
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_ENCODING => "",
//         CURLOPT_MAXREDIRS => 10,
//         CURLOPT_TIMEOUT => 30,
//         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//         CURLOPT_CUSTOMREQUEST => "POST",
//         CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded',],
//         CURLOPT_POSTFIELDS => $postdata,
//       ]);

//     curl_setopt(
//         $ch,
//         CURLOPT_HEADERFUNCTION,
//         function ($curl, $header) use (&$headers) {
//             $len = strlen($header);
//             $header = explode(':', $header, 2);
//             if (count($header) < 2) { // ignore invalid headers
//                 return $len;
//             }
//             $headers[strtolower(trim($header[0]))][] = trim($header[1]);
//             return $len;
//         }
//     );

//     $result = curl_exec($ch);
//     curl_close($ch);

//     return var_export($headers, true);
// }

// $eci='06';
// $cavv='07981001436880000000012C4841037500000000';
// $xid='07981001436880000000012C4841037500000000';
// $ref3d='1744TFPFC00027488';
// $pw_res = payworks_call('3.00', '4320490101177630', '02/25', '710', $eci, $cavv, $ref3d, $xid);

// echo $pw_res;

// \SMSApi::sendWhatsappMessage('6563387747', 'Hola, este es un mensaje de prueba');

/*\SMSApi::sendSMS('6561491206', 'test de sms FPFCH', '52');

return;*/

// \SMSApi::sendTwilioSMS('6563387747', 'Hola, este es un mensaje de prueba desde la app de tramites de la UACJ', '52');



// ------------------------- test de pagos
$recarga = \Util\TPApi::recargaSaldo('tptest001', 2041.76, 'testref002', 3);
echo "resultado de recarga TP" . $recarga;
$recarga = \Util\TPApi::recargaSaldo('tptest001', 4083.52, 'testref002', 3);
echo "resultado de recarga TP" . $recarga;


//$recarga = \Util\LEApi::recargaSaldo('FPFC00080149', 7056.88, 2, 1); // 2=debito
//echo "resultado de recarga LE" . $recarga;
//$recarga = \Util\LEApi::recargaSaldo('FPFC00080149', 10585.32, 2, 1); // 2=debito
//echo "resultado de recarga LE" . $recarga;
//$recarga = \Util\LEApi::recargaSaldo('FPFC00080149', 2058.00, 2, 1); // 2=debito
//echo "resultado de recarga LE" . $recarga;
