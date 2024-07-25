<?php

declare(strict_types=1);
ini_set("display_errors", "On");
require __DIR__ . "/vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable('/var/www/liexapiconf/v1');
$dotenv->load();

set_time_limit(8);

function bn_log($msg)
{
    error_log("liex banorte: " . $msg);
}

function savePaymentError(
    $p_id_vehicle,
    $p_amount,
    $p_payment_method,
    $p_payment_dt,
    $p_bridge,
    $p_card_type,
    $p_card_bank,
    $p_card_brand,
    $p_payment_processor,
    $p_auth_code,
    $p_reference,
    $p_vmarca,
    $p_vlinea,
    $p_vplaca,
    $p_vmodelo,
    $p_vcolor,
    $p_vtag,
    $p_vtipo,
    $p_id_user,
    $p_username,
    $p_sentri,
    $p_result,
    $p_notes
) {
    $pago = new \Data\PaymentsModel();
    $pago->id_vehicle = $p_id_vehicle;
    $pago->amount = $p_amount;
    $pago->payment_method = $p_payment_method;
    $pago->payment_dt = $p_payment_dt;
    $pago->payment_processor = $p_payment_processor;
    $pago->bridge = $p_bridge;
    $pago->card_type = $p_card_type;
    $pago->card_bank = $p_card_bank;
    $pago->card_brand = $p_card_brand;
    $pago->auth_code = $p_auth_code;
    $pago->reference = $p_reference;

    $pago->vmarca = $p_vmarca;
    $pago->vlinea = $p_vlinea;
    $pago->vplaca = $p_vplaca;
    $pago->vmodelo = $p_vmodelo;
    $pago->vcolor = $p_vcolor;
    $pago->vtag = $p_vtag;
    $pago->vtipo = $p_vtipo;
    $pago->id_user = $p_id_user;
    $pago->username = $p_username;
    $pago->sentri = $p_sentri;
    $pago->result = $p_result;
    $pago->notes = $p_notes;

    $pago->save();
}

function echo_response($r)
{
    $resp = file_get_contents(__DIR__ . '/bn.html');
    $resp = str_replace("@@content", $r, $resp);
    echo $resp;
}

function payworks_call($p_amount, $p_cn, $p_fe, $p_sc, $p_eci, $p_cavv, $p_ref3d, $p_xid)
{
    $pw_url = $_ENV["PW_URL"];
    $pw_merchant = $_ENV["PW_MERCHANT"];
    $pw_user = $_ENV["PW_USER"];
    $pw_pass = $_ENV["PW_PASS"];
    $pw_terminal = $_ENV["PW_TERMINAL"];
    $pw_mode = $_ENV["PW_MODE"];

    $postdata = "MERCHANT_ID=$pw_merchant&USER=$pw_user&PASSWORD=$pw_pass&CMD_TRANS=AUTH";
    $postdata .= "&TERMINAL_ID=$pw_terminal&AMOUNT=$p_amount&MODE=$pw_mode&CARD_NUMBER=$p_cn";
    $postdata .= "&CARD_EXP=$p_fe&SECURITY_CODE=$p_sc&ENTRY_MODE=MANUAL&RESPONSE_LANGUAGE=ES";
    $postdata .= "&ESTATUS_3D=200&ECI=$p_eci&CAVV=$p_cavv&VERSION_3D=2"; //REFERENCIA3D=$p_ref3d&

    if ($p_xid) {
        $postdata .= "&XID=$p_xid";
    }

    // \Util\Logger::log("payworks_request: " . $postdata);
    // error_log("payworks_request: " . $postdata);

    $headers = [];
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_URL => $pw_url,
        // CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded',],
        CURLOPT_POSTFIELDS => $postdata,
    ]);

    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    curl_setopt(
        $ch,
        CURLOPT_HEADERFUNCTION,
        function ($curl, $header) use (&$headers) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) { // ignore invalid headers
                return $len;
            }
            $headers[strtolower(trim($header[0]))][] = trim($header[1]);
            return $len;
        }
    );

    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    // return var_export($headers, true) . "ERR: " . $err;
    return $headers;
}

$bncodes = [
    102 => 'Tarjeta inválida. / Invalid card.',
    200 => 'Transacción es segura y se puede enviar a Payworks.',
    201 => 'Se detecto un error general en el sistema de Visa o Master Card, se recomienda esperar unos momentos para reintentar la transacción.',
    421 =>  'Servicio 3D Secure no está disponible, se recomienda esperar unos momentos para reintentar la transacción.',
    422 =>  'Hubo un problema genérico al momento de realizar la autenticación, no se debe enviar la transacción a Payworks.',
    423 =>  'Autenticación no fue exitosa, no se debe enviar la transacción a Payworks ya que el comprador no se pudo autenticar con éxito.',
    424 =>  'Autenticación 3D Secure no fue completada. NO se debe enviar a procesar la transacción al motor de pagos Payworks, ya que la persona no está ingresando correctamente la contraseña 3D Secure.',
    425 =>  'Autenticación Inválida. Indica que definitivamente NO se debe enviar a procesar la transacción a Payworks, ya que la persona no está ingresando correctamente la contraseña3D Secure.',
    426 =>  'Afiliación no encontrada. Indica que NO existe la afiliación ingresada por el usuario en el programa 3D Secure.',
    430 =>  'Tarjeta de Crédito nulo, la variable Card se envió vacía.',
    431 =>  'Fecha de expiración nulo, la variable Expires se envió vacía.',
    432 =>  'Monto nulo, la variable Total se envió vacía.',
    433 =>  'Id del comercio nulo, la variable MerchantId se envió vacía.',
    434 =>  'Liga de retorno nula, la variable ForwardPath se envió vacía.',
    435 =>  'Nombre del comercio nulo, la variable MerchantName se envió vacía.',
    436 =>  'Formato de TC incorrecto, la variable Card debe ser de 16 dígitos.',
    437 =>  'Formato de Fecha de Expiración incorrecto, la variable Expires debe tener el siguiente formato: MM/YY donde MM se refiere al mes, YY se refiere al año de vencimiento de la tarjeta.',
    438 =>  'Fecha de Expiración incorrecto, indica que el plástico esta vencido.',
    439 =>  'Monto incorrecto, la variable Total debe ser un número menor a 999,999,999,999.## con la fracción decimal opcional, esta debe ser a lo más de 2 décimas.',
    440 =>  'Formato de nombre del comercio incorrecto, debe ser una cadena de máximo 25 caracteres alfanuméricos.',
    441 =>  'Marca de Tarjeta nulo, la variable CardType se envió vacía.',
    442 =>  'Marca de Tarjeta incorrecta, debe ser uno de los siguientes valores: VISA (para tarjetas Visa) o MC (para tarjetas Master Card).',
    443 =>  'CardType incorrecto, se ha especificado el CardType como VISA, sin embargo, el Bin de la tarjeta indica que esta no es Visa.',
    444 =>  'CardType incorrecto, se ha especificado el CardType como MC, sin embargo, el Bin de la tarjeta indica que esta no es Master Card.',
    445 =>  'CardType incorrecto, se ha especificado el CardType como AMEX, sin embargo, el programa no acepta esta marca por el momento.',
    446 =>  'Monto incorrecto, la variable Total debe ser superior a 1.0 pesos.',
    447 =>  'Referencia 3D nula, la variable reference3D se envió vacía.',
    448 =>  'Cert3D nula, la variable Cert3D se envió vacía.',
    498 =>  'Transacción expirada. Indica que la transacción sobrepasó el límite de tiempo de respuesta esperado.',
    499 =>  'Usuario excedió el tiempo de respuesta. Indica que el usuario tardó en capturar la información de 3D Secure mayor al tiempo esperado.',
    450 =>  'El módulo 3D Secure Plus se encuentra inhabilitado para la afiliación (MerchantId) ingresada.',
    451 =>  'El `XXXX` es un campo obligatorio. / `XXXX` - This field is required.',
    452 =>  'El campo `XXXX` excede la longitud permitida de `XXXX` caracteres. / `XXXX` is too large (max length of `XXXX` characters).',
    453 =>  'El campo `XXXX` no es un numérico. / `XXXX` - This field only allows numeric values.',
    454 =>  'No se pudo recibir respuesta de cruise hybrid lookup. / It can`t reply cruise hybrid lookup response.',
    455 =>  'Error al crear el JWT Claim. / Failed creation JWT Claim.',
    456 =>  'JWT mal formado. / JWT corrupted.'
];

function puente_desc($p_stall_id)
{
    switch($p_stall_id) {
        case 104:
            return "LERDO";
        case 105:
            return "ZARAGOZA";
        default:
            return "TP";
    }
}

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

    echo json_encode($err);
    $f3->error($f3->get('ERROR.code'));
});

$req_dump = $_REQUEST;
// bn_log("REQ_DATA: " . $req_dump);

$body = file_get_contents('php://input');
// bn_log("REQ_BODY: " . $body);

if (!isset($req_dump['Estatus'])) {
    bn_log("No se recibio respuesta de 3DSecure");
    $b_res = "<h3>ERROR: Esta pagina solo debe ser invocada por 3DSecure.</h3>";
    echo_response($b_res);
    exit;
}

$b3dres = "";

if ($req_dump['Estatus'] == '200') {

    if (!isset($req_dump['ECI']) || !isset($req_dump['CAVV']) || !isset($req_dump['REFERENCIA3D'])) {  //!isset($req_dump['XID']) ||
        bn_log("No se recibieron todos los parametros de 3DSecure. Request: " . var_export($req_dump, true));
        $b_res = "<h4>ERROR: No se recibieron todos los parametros de 3DSecure.</h4>";
        echo_response($b_res);
        exit;
    }

    $b3d = new \Data\Banorte3dModel();
    $b3d->status = $req_dump['Estatus'];
    $b3d->eci = $req_dump['ECI'];
    $b3d->cavv = $req_dump['CAVV'];
    $b3d->xdi = $req_dump['XID'];
    $b3d->ref3d = $req_dump['REFERENCIA3D'];
    $b3d->save();

    $b3d = new \Data\BntcModel();
    $fnd = $b3d->load(array('ref3d = ?', $req_dump['REFERENCIA3D']), ['order' => 'id desc', 'limit' => 1]);
    if ($fnd === false) {
        bn_log("No se encontro la referencia 3D en la base de datos. REQUEST: " . var_export($req_dump, true));
        $b_res = "<h4>ERROR: No se encontro la referencia 3D en la base de datos (llamada a 3DSecure sin llamada previa a API).</h4>";
        echo_response($b_res);
        exit;
    }

    $tcn = $b3d->tcn;
    $fe = $b3d->fe;
    $cs = $b3d->cs;
    $tag = $b3d->tag;
    $ref3d = $b3d->ref3d;
    $monto = $b3d->monto;
    $tipopago = $b3d->tipopago;
    $monto_str = "$".number_format(floatval($monto), 2);

    $b3d->erase();

    $pw_res = "";

    $pw_res = payworks_call($monto, $tcn, $fe, $cs, $req_dump['ECI'], $req_dump['CAVV'], $req_dump['REFERENCIA3D'], $req_dump['XID']);
    // --------------headers de respuesta
    // PAYW_RESULT
    // text
    // payw_code
    // MERCHANT_ID
    // REFERENCE

    $pw_cod_aut = isset($pw_res['codigo_aut'][0]) ? $pw_res['codigo_aut'][0] : "---";
    $pw_referencia = isset($pw_res['referencia'][0]) ? $pw_res['referencia'][0] : "---";

    $pw_resultado = $pw_res['resultado_payw'][0];
    $pw_texto = $pw_res['texto'][0];
    $pw_texto = urldecode($pw_texto);

    // datos que solo llegan en PRODUCCION
    $tipo_tarjeta = \array_key_exists('tipo_tarjeta', $pw_res) ? $pw_res['tipo_tarjeta'][0] : "---";
    $banco_emisor = \array_key_exists('banco_emisor', $pw_res) ? $pw_res['banco_emisor'][0] : "---";
    $marca_tarjeta = \array_key_exists('marca_tarjeta', $pw_res) ? $pw_res['marca_tarjeta'][0] : "---";

    $recarga = [];
    if ($pw_resultado == 'A') {
        // bn_log('PAGO APROBADO: ' . var_export($pw_res, true));

        // localicar vehiculo en base a TAG
        $vehiculo = new \Data\VehiclesModel();
        $fnd = false;
        switch($tipopago) {
            case 1:
                $fnd = $vehiculo->load(array('tag = ? and tipo = 0', $tag));
                break;
            case 2:
                $fnd = $vehiculo->load(array("tag = ? and tipo = 1 and ctl_contract_type = 'C'", $tag));
                break;
            case 3:
                $fnd = $vehiculo->load(array("tag = ? and tipo = 1 and ctl_contract_type = 'V'", $tag));
                break;
            case 4:
                $fnd = $vehiculo->load(array("tag = ? and tipo = 1 and ctl_contract_type = 'M'", $tag));
                break;
            case 5:
                $fnd = $vehiculo->load(array('tag = ? and tipo = 2', $tag));
                break;
            default:
                bn_log("Tipo de pago no valido. REQUEST: " . var_export($req_dump, true) . " BNTC: " . var_export($b3d, true) . " PAYW: " . var_export($pw_res, true));
                $b_res = "<h4>ERROR: Tipo de pago no valido.</h4>";
                echo_response($b_res);
                exit;
        }

        if ($fnd === false) {
            bn_log("No se encontro el vehiculo en la base de datos. REQUEST: " . var_export($req_dump, true));
            $b_res = "<h4>ERROR: No se encontro el vehiculo en la base de datos <strong>$tag</strong>. </h4>";
            echo_response($b_res);
            exit;
        }

        $user = new \Data\UserModel();
        $fnd = $user->load(array('id = ?', $vehiculo->id_user));
        if ($fnd === false) {
            bn_log("No se encontro el usuario en la base de datos. REQUEST: " . var_export($req_dump, true));
            $b_res = "<h4>ERROR: No se encontro el usuario en la base de datos <strong>$tag</strong>. </h4>";
            echo_response($b_res);
            exit;
        }

        // guardar datos de pago en tabla 'payments'
        $pago = new \Data\PaymentsModel();
        $pago->id_vehicle = $vehiculo->id;
        $pago->amount = $monto;
        $pago->payment_method = $tipo_tarjeta;
        $pago->payment_dt = date('Y-m-d H:i:s');
        $pago->payment_processor = 'BANORTE';
        $pago->bridge = puente_desc($vehiculo->ctl_stall_id);
        $pago->card_type = $tipo_tarjeta;
        $pago->card_bank = $banco_emisor;
        $pago->card_brand = $marca_tarjeta;
        $pago->auth_code = $pw_cod_aut;
        $pago->reference = $pw_referencia;

        $pago->vmarca = $vehiculo->marca;
        $pago->vlinea = $vehiculo->linea;
        $pago->vplaca = $vehiculo->placa;
        $pago->vmodelo = $vehiculo->modelo;
        $pago->vcolor = $vehiculo->color;
        $pago->vtag = $vehiculo->tag;
        $pago->vtipo = $vehiculo->tipo;
        $pago->id_user = $vehiculo->id_user;
        $pago->username = $user->fullname;
        $pago->sentri = $user->sentri_number ? $user->sentri_number : '';

        $pago->save();

        $recarga = '---';

        try {

            if ($vehiculo->tipo == 1) {
                $recarga = \Util\LEApi::recargaSaldo(
                    $tag,
                    $monto,
                    \trim(\strtoupper($tipo_tarjeta)) == 'DEBITO' ? 2 : 3,
                    $tipopago
                );

                $err_msg = "Resultado de recarga en CONTROLES (LE): " . $tag . " "
                    . var_export($monto, true) . " "
                    . var_export($tipo_tarjeta, true)
                    . " RECARGA:" . var_export($recarga, true);
                bn_log($err_msg);

                $rec_txt = var_export($recarga, true);

                if ($recarga == null || strpos($rec_txt, 'errors') !== false ) {

                    $error_msg = "Error al actualizar saldo en CONTROLES (LE): " . $tag
                        . " RECARGA:" . var_export($recarga, true);
                    $pago->result = 'ERROR';
                    $pago->notes = $err_msg;
                    $pago->save();
                    bn_log($err_msg);

                    \Util\Mail::sendPaymentErrorEmail($_ENV["CTL_ERROR_EMAILS="], 'Administrador de fideicomiso de puentes', $error_msg);

                    $b_res = "<h4>Error al actualizar su saldo en CONTROLES (LE) para TAG: <strong>$tag</strong>.</h4>
                        <p>Por favor tome nota de los siguientes datos y contacte a un operador. <br/>
                    Referencia: $pw_referencia<br />
                    Codigo de autorización: $pw_cod_aut<br /></p>";
                    echo_response($b_res);
                    exit;
                }
            } else {
                $recarga = \Util\TPApi::recargaSaldo(
                    $tag,
                    $monto,
                    $pw_res['referencia'][0] . '-' . $pw_res['codigo_aut'][0],
                    \trim(\strtoupper($tipo_tarjeta)) == 'DEBITO' ? 3 : 4
                );
                bn_log("Resultado de recarga en CONTROLES (TP): " . $tag . " RECARGA:" . var_export($recarga, true));
                if (!\array_key_exists('saldoFinal', $recarga)) {

                    $error_msg = "Error al actualizar saldo en CONTROLES (TP): " . $tag
                        . " RECARGA:" . var_export($recarga, true);
                    $pago->result = 'ERROR';
                    $pago->notes = $error_msg;
                    $pago->save();
                    bn_log($error_msg);

                    \Util\Mail::sendPaymentErrorEmail($_ENV["CTL_ERROR_EMAILS="], 'Administrador de fideicomiso de puentes', $error_msg);

                    $b_res = "<h4>Error al actualizar su saldo en CONTROLES (TP) para TAG: <strong>$tag</strong>.</h4>
                        <p>Por favor tome nota de los siguientes datos y contacte a un operador. <br/>
                    Referencia: $pw_referencia<br />
                    Codigo de autorización: $pw_cod_aut<br /></p>";
                    echo_response($b_res);
                    exit;
                }
            }

        } catch (\Exception $e) {

            $error_msg = "Error al registrar recarga de saldo. RECARGA: " . var_export($recarga, true) . "\n\nERROR: " . $e->getMessage();
            $pago->result = 'ERROR';
            $pago->notes = $error_msg;
            $pago->save();
            bn_log($error_msg);

            $b_res = "<h4>ERROR: Error al registrar recarga de saldo.</h4>";
            echo_response($b_res);
            exit;
        }

        if ($vehiculo->tipo == 0) {
            $saldoAnterior = $recarga['saldoAnterior'];
            $saldoFinal = $recarga['saldoFinal'];
            $saldoAnterior = "$".number_format(floatval($saldoAnterior), 2);
            $saldoFinal = "$".number_format(floatval($saldoFinal), 2);
        } else {
            $saldoAnterior = "$".number_format(floatval($vehiculo->saldo), 2);
            $saldoFinal = "$".number_format(floatval($vehiculo->saldo) + floatval($monto), 2);
        }

        // $pw_cod_aut = $pw_res['codigo_aut'][0];
        // $pw_referencia = $pw_res['referencia'][0];
        $pw_res = "<h4>Transacción aprobada</h4>
            <p>$pw_texto<br/>
            Referencia: $pw_referencia<br />
            Codigo de autorización: $pw_cod_aut<br />
            Se aplicó pago de <strong>$monto_str</strong> MXN al TAG <strong>$tag</strong><br />
            Saldo anterior: $saldoAnterior MXN<br />
            Saldo actual: <strong>$saldoFinal</strong> MXN<br />
            </p>
        ";
    } else {
        $pw_res = "<h4>Transacción rechazada</h4>
        <p>$pw_texto<br/>Por favor intente nuevamente.</p>
    ";
    }

    // $pw_res .= var_export($req_dump, true);
    // $b3dres = "<p>$pw_texto</p>";

    $b3dres = $pw_res;

} else {
    $ec = $req_dump['Estatus'];
    $ed = $bncodes[$req_dump['Estatus']];
    $b3dres = "<h3>Transacción rechazada por 3DSecure</h3><h4>$ec: $ed</h4>";
}

echo_response($b3dres);
