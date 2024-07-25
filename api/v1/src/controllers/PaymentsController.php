<?php

// PaymentsController.php
// Funciones para procesar informacion de pagos
// LineaExpressApp

namespace Controllers;

class PaymentsController extends BaseController
{
    private const REQ_PAYMENT_PARAMS = ['data'];
    private const REQ_PAYMENT_DATA_PARAMS = ['MONTO', 'NUMERO_TARJETA', 'FECHA_EXP', 'CODIGO_SEGURIDAD', 'TAG', 'TP'];

    private const PRIV_KEY = "-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCN0klLkGvn+OuB
j56DZUscdhWCyi8bJV7XwI8Ask1vfgpG83MMdVZ2AWuoRVKNWezd8bVFGmtEkixO
zwgaazOjieAM44l+zyMmfhdONOzBD7YkU7bslSyNcbOM7otPJLEO+ozcypNG0ufC
3BJHtcSTQuAlMuPjSPWxq0HDPxtVEPXVeGboYF10xnUZBGYXUYCR1agh4TJCa2l9
e/iotICKRYAMvXgCgVUfrNbgSIEdKkfSndU99j8z2Z6pgpzvGpN8wIykjkSAv19j
8TfAlj3uJeDecfSQw+9PlkP+O1UlZzWF98IwagyGGkBgWjUK0AwRFDC0rAjWqqph
wLRzVj3RAgMBAAECggEACJf/w4GwnDmn8a2lfhJPAx4/rvF3+cXhsaK0N6dWTGV/
k22QoOZiUxvCbYun1TNoCEnrgVC4w8q0vyDJ/anV0vjZmAZYsFO2blBxMpa/6vH8
CEb/XGYssTR83BMfY3AWf5EcpIts8bt5ekQSIoDH9OF2SiLDjuP+qWG2hjGGgzjB
B2tZI8L0oq5nQWUhidG6smWTNIKO247EMY/gZ14Iq6euez9+L15D7KEX4RLDLBZ7
vCsBtDeg23NIIjO0ZesO9YUJwpWhF+IP0NV0lmQudfQYqGYFVDeLyybQxvI6DUME
rCxSy7tOAw/JqsfU8zhokZ+lzsUhVtFB554gX5AyAQKBgQC5/OZQc+eA4YLJWdEl
VRFtjDXHfgCKEQmVek8+TAuPwTHHQgQkY0KR1UmtrtEgzxKbghP5qHWb1xiLe5C/
YoaQuar6VQihn6F91cVzA5xNAntMUOYbCR7PDBfUjmHYJTu0APLNnEYRQpdFedrm
1EluIp0LKWW+a5o/KgshwVgEgQKBgQDDNTI+OMXepTcCByYK0oKRM2h/NubF/+5H
DusrxPtSf+uyiyHsNzh9tY52rvvAfnZRErf9UOygIlNJ2G2xaoMY2hDMoDy7ncp4
BP3cDTMhLcURSi/MK64V8/BpO0eLHm5Tj5+znLlLi5g0vFadQf5KYozHTdAS824a
GTtypRBRUQKBgQCkp8SuzS3S59MFt0J1ro4zUcH6zw5jLRoy/4lQObqylfMf2M4Z
+NZijVUhMndqeGicy9grWnxkb1UHh2lqRiujzPwVi1qM2+n4oVygqj2h3+SQd75/
iN+Bpc8jGrp8xNnKTlymswdFGJMbqvmlCaPIoVQvIYM1xcVUzj/0rlriAQKBgDjD
Uj3XomoQnsM/Mue4uPDiAwPjAg4XUsFk7CuJFw+xVOdRH+chU5LyZV/LmDcsTtla
WRgXHQefk5qIjbUmZMKoPnRSL/badlKaGPuQ9wox1fkmGmoDVZbanVDsROyGR8yS
mfzPmO4HxJWpIoKN+xTEPW2DCftIv/5k0e2PZARRAoGACINEI5CKOcXodwzYbWk5
D+1uN2IQ57mmgvtqsm2GLqbxms486w/tX7X9Scn4l83J5houlrARHcr14sH3XBBm
OqmuSoEk/SB3WfCFecWkdIKty/vRMqyTuth66kQvDh5yelkbAHggvEmlB343IGOa
7nlOTayB9Gf4S7Tj8t0Q+0s=
-----END PRIVATE KEY-----";

    public function decrypt($data, $pkey)
    {
        $key = openssl_pkey_get_private($pkey);
        openssl_private_decrypt($data, $decrypted, $key);
        return $decrypted;
    }

    public function getAutoFillData()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['tag'], $params);

        $tag = $params['tag'];
        $veh = new \Data\VehiclesModel();
        $veh->load(array('tag = ?', $tag));

        if ($veh->dry()) {
            \http_response_code(404);
            echo json_encode(array('message' => 'No se encontró el vehículo'));
            die();
        }

        $res = [];
        $idu = $veh->id_user;
        $paf = new \Data\PaymentAFModel();
        $paf->load(array('id = ?', $idu));
        if ($paf->dry()) {
            $user = new \Data\UserModel();
            $user->load(array('id = ?', $idu));
            $res['numero_celular'] = $user->phone;
            $res['correo'] = $user['userlogin'];
        } else {
            $res = $paf->cast();
        }

        echo json_encode($res);
    }

    public function saveAutoFillData()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $params = $this->getParamsFromRequestBodyOrDie();

        $veh = new \Data\VehiclesModel();
        $veh->load(array('tag = ?', $params['tag']));
        if ($veh->dry()) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No existe vehiculo con el tag especificado.' ]);
            die;
        }

        $paf = new \Data\PaymentAFModel();
        $paf_found = $paf->load(array('id = ?', $veh->id_user));
        if ($paf_found === false) {
            $paf->reset();
            $paf->id = $veh->id_user;
        }
        $paf->nombre = $params['nombre'] ? $params['nombre'] : '';
        $paf->apellido = $params['apellido'] ? $params['apellido'] : '';
        $paf->calle = $params['calle'] ? $params['calle'] : '';
        $paf->ciudad = $params['ciudad'] ? $params['ciudad'] : '';
        $paf->numero_celular = $params['numero_celular'] ? $params['numero_celular'] : '';
        $paf->correo = $params['correo'] ? $params['correo'] : '';
        $paf->cp = $params['cp'] ? $params['cp'] : '';

        $paf->save();

        echo json_encode([ 'message' => 'Datos AF guardados correctamente.' ]);
    }

    public function checkTagPayable()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['tag', 'tipo'], $params);

        // look for car corresponding to tag
        $veh = new \Data\VehiclesModel();
        $tipo = intval($params['tipo']);
        $tag = $params['tag'];

        switch($tipo) {
            case 1:
                $veh->load(array('tag = ? and tipo = 0', $tag));
                break;
            case 5:
                $veh->load(array('tag = ? and tipo = 2', $tag));
                break;
            case 2:
                $veh->load(array("tag = ? and tipo = 1 and ctl_contract_type = 'C'", $tag));
                break;
            case 3:
                $veh->load(array("tag = ? and tipo = 1 and ctl_contract_type = 'V'", $tag));
                break;
            case 4:
                $veh->load(array("tag = ? and tipo = 1 and ctl_contract_type = 'M'", $tag));
                break;
            default:
                http_response_code(400);
                echo json_encode([ 'message' => 'Tipo de tag inválido.' ]);
                die;
        }

        // $veh->load(array('tag = ?', $params['tag']));
        if ($veh->dry()) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No existe vehiculo con el tag especificado.' ]);
            die;
        }

        $res = ['payable' => true];
        if ($veh->ctl_contract_type) {
            $ct = \trim(\strtoupper($veh->ctl_contract_type));
            if (\in_array($ct, ['V', 'M'])) {
                $ed = \DateTime::createFromFormat('Y-m-d', $veh->clt_expiration_date);
                $ex = new \DateTime();

                // echo $ex->format('Y-m-d'), "\n";
                // echo $ed->format('Y-m-d'), "\n";
                $d1 = $ex->diff($ed, false)->format("%r%a");
                // $d2 = $ed->diff($ex, false)->format("%r%a");
                // echo $d1, "\n";
                // echo $d2, "\n";

                if ($d1 > 90) {
                    $res = ['payable' => false, 'days' => $ed->diff(new \DateTime())->days ];
                }
            }
        }

        echo json_encode($res);
    }

    public function processPayment()
    {
        // $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(self::REQ_PAYMENT_PARAMS, $params);

        $encoded = $params['data'];
        $decoded = base64_decode($encoded);

        $decrypted = $this->decrypt($decoded, self::PRIV_KEY);
        $decrypted = strtolower($decrypted);

        $paymentData = json_decode($decrypted, true);
        if ($paymentData == null) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Error al decodificar los datos de pago (JSON malformado).' ]);
            return;
        }
        // {"MONTO":1000, "NUMERO_TARJETA": 1234123412341234, "FECHA_EXP":"1222", "CODIGO_SEGURIDAD":123, "TAG":"ABC12345678", "REF3D": "ABC12345678"}
        $this->f3->get('DB')->exec("delete from bntc where ref3d = :r", array(':r' => $paymentData['ref3d']));

        $paymentData = array_map('trim', $paymentData);

        foreach ($paymentData as $key => $value) {
            if (empty($value)) {
                http_response_code(400);
                echo json_encode([ 'message' => 'Todos los parametros deben tener valores:' . $key ]);
                die;
            }
            $paymentData[$key] = strtoupper($value);
        }

        // look for car corresponding to tag
        $veh = new \Data\VehiclesModel();
        $veh->load(array('tag = ?', $paymentData['tag']));
        if ($veh->dry()) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No existe vehiculo con el tag especificado.' ]);
            die;
        }

        $tcd = new \Data\BntcModel();
        $tcd->monto = \floatval($paymentData['monto']);
        $tcd->tcn = $paymentData['numero_tarjeta'];
        $tcd->fe = \preg_replace('/[^0-9]/', '', $paymentData['fecha_exp']);
        $tcd->cs = $paymentData['codigo_seguridad'];
        $tcd->tag = \strtoupper($paymentData['tag']);
        $tcd->ref3d = \strtoupper($paymentData['ref3d']);
        $tcd->tipopago = \strtoupper($paymentData['tipopago']);
        // echo "record casteadio:\n";
        // \var_dump($tcd->cast());
        $tcd->save();

        echo json_encode(['message' => "Datos recibidos correctamente, Ya puede iniciar proceso de 3DSecure."]);
    }

    public function processPaymentWithStripeAPI($card_number, $exp_month, $exp_year, $cvc, $amount, $currency, $description)
    {
        // use stripe api to process payment
        $stripe = new \Stripe\StripeClient("sk_test_51HsXqzBjDwMlL5QS2PJlgF0qIhgOOQNekAvhVPYZtCphg1zhN48qj7SIxyyvqqxpQxAbm6s6oTHfBSxjupmEfceS00m7jfG0Tb");
        $product = $stripe->products->create([
           'name' => 'Fideicomiso de puentes',
           'description' => 'Pago de puentes',
         ]);
        echo "Success! Here is your starter subscription product id: " . $product->id . "\n";

        $price = $stripe->prices->create([
          'unit_amount' => $amount,
          'currency' => 'mxp',
          'recurring' => ['interval' => 'month'],
          'product' => $product['id'],
        ]);
        echo "Success! Here is your premium subscription price id: " . $price->id . "\n";
    }
}
