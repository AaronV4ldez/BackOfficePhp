<?php

// LEApi.php
// Funciones para interactuar con la API de Controles-LineaExpress
// LineaExpressApp

namespace Util;

const LE_API_URL_USUARIO = "/Usuarios/"; // + sentri/email
const LE_API_URL_CARS_CROSS = "/Cruces/@idu/vehiculos/@idv/Cruces/@fi/@ff";
const LE_API_URL_RECARGA = "/Transacciones/@idu/vehiculos/@idv";
//const LE_API_URL_CARS_CROSS_NEW = "/ConsultaCrucesTag/@tag/@fi/@ff";
const LE_API_URL_CARS_CROSS_NEW = "/ConsultaCrucesTag/@tag/20240601/20240701";


class LEApi
{
    public static function getUserInfo($sentry, $email)
    {
        $tpApiKey = $_ENV["LE_API_KEY"];
        $baseURL = $_ENV["LE_BASE_URL"];
        $fullUrl = $baseURL . LE_API_URL_USUARIO . $sentry . "/" . $email;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Authorization:Bearer " . $tpApiKey;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            return \json_encode([]);
        } else {
            return $result;
        }
    }

    public static function getUserCars($id)
    {
        $tpApiKey = $_ENV["LE_API_KEY"];
        $baseURL = $_ENV["LE_BASE_URL"];
        $fullUrl = $baseURL . LE_API_URL_USUARIO . $id . "/vehiculos";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Authorization:Bearer " . $tpApiKey;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            return \json_encode([]);
        } else {
            return $result;
        }
    }

    public static function getCarLatestCrossings($user_id, $car_id)
    {
        $tpApiKey = $_ENV["LE_API_KEY"];
        $baseURL = $_ENV["LE_BASE_URL"];
        $fullUrl = $baseURL . LE_API_URL_CARS_CROSS;

        $fullUrl = \str_replace("@idu", $user_id, $fullUrl);
        $fullUrl = \str_replace("@idv", $car_id, $fullUrl);
        $fullUrl = \str_replace("@fi", date("Y-m-d", \strtotime("-360 days")), $fullUrl);
        $fullUrl = \str_replace("@ff", date("Y-m-d"), $fullUrl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Authorization:Bearer " . $tpApiKey;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        // order result array by crossing_date field
        $result = \json_decode($result, true);
        \usort($result, function ($a, $b) {
            return \strtotime($b["crossing_date"]) - \strtotime($a["crossing_date"]);
        });

        $result = \json_encode($result);

        if ($result === false) {
            return \json_encode([]);
        } else {
            return $result;
        }
    }

    public static function recargaSaldo($tag, $amount, $payment_method = 2, $tipopago = 1)
    {
        $ApiKey = $_ENV["LE_API_KEY"];
        $baseURL = $_ENV["LE_BASE_URL"];
        $fullUrl = $baseURL . LE_API_URL_RECARGA;

        $car = new \Data\VehiclesModel();
        $car = $car->load(array("tag = :tag and tipo = 1 and (clt_expiration_date is null or clt_expiration_date >= current_timestamp())", ":tag" => $tag));

        if ($car === false) {
            error_log("LE-PAGOS ERROR: Se intento hacer recarga pero no se encontró el vehículo con tag: " . $tag);
            // echo "LE-PAGOS ERROR: Se intento hacer recarga pero no se encontró el vehículo con tag: " . $tag . "\n";
            return;
        }

        // ------------------------------ inicia cambio para sacar valor de "asignacion" de la tabla de configuracion
        $cfg = new \Data\ConfigModel();
        $cf = $cfg->load();
        // get names of all columns starting with "anual" or "saldo" and does not end with "_r"
        $pcols = \array_filter($cf->cast(), function ($key) {
            return \preg_match("/^(anual|saldo)/", $key) && !\preg_match("/_r$/", $key);
        }, ARRAY_FILTER_USE_KEY);

        $colreal = "";

        // interate $pcols array and print each key and value
        foreach ($pcols as $key => $value) {
            if ($value == $amount) {
                $colreal = $key;
                break;
            }
        }

        if ($colreal != "") {
            $amount = $cf[$colreal . "_r"];
            $comis = $cf[$colreal] - $cf[$colreal . "_r"];
        } else {
        
            // maroma para pagos reales vs pagos pato
            if ($amount > 1000) {
                $comis = \fmod($amount, 1000);
                $amount = $amount - $comis;
            } else {
                $comis = 0;
            }
        
        }
        // ------------------------------ fin de cambio para sacar valor de "asignacion"
        

        // controles no acepta decimales en este campo
        $amount = \intval($amount);

        \error_log("liex controles LE: Se intenta hacer recarga de " . $amount . " al vehiculo con tag: " . $tag . " y metodo de pago " . $payment_method);

        $fullUrl = \str_replace("@idu", $car["ctl_user_id"], $fullUrl);
        $fullUrl = \str_replace("@idv", $car["ctl_id"], $fullUrl);

        $params = [
            "stall_id" => $car["ctl_stall_id"],
            "contract_tag" => $car["tag"],
            "contract_type"=> $car["ctl_contract_type"],
            "crossings_quantity"=> \in_array($tipopago, [3,4]) ? null : $amount,
            "payment_method" => $payment_method, // 2=DEBITO, 3=CREDITO
            "operation_price" => $amount,
            "operation_charge" => $comis //$amount * 0.0208809840425,
        ];
        $postdata = json_encode($params);

        \error_log("liex controles LE: parametros de la recarga: " . \var_export($params, true) . " URL: " . $fullUrl);

        // return "test le";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Authorization:Bearer " . $ApiKey;
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);

        curl_close($ch);

        \error_log("liex controles LE: Resultado de la recarga: " . \var_export($result, true));

        return $result;
    }

    // function to iterate an asociative array and print it with dot notation
    public static function printArray($array, $parent = "")
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                self::printArray($value, $parent . $key . ".");
            } else {
                echo $parent . $key . " = " . $value . "\n" ;
            }
        }
    }
    
    //function new cruces
    
    public static function getCarLatestCrossingsnew($tag)
    {
        $tpApiKey = $_ENV["TP_API_KEY"];
        $baseURL = $_ENV["TP_BASE_URL"];
        $fullUrl = $baseURL . LE_API_URL_CARS_CROSS_NEW;

        $fullUrl = \str_replace("@tag", $tag, $fullUrl);
        $fullUrl = \str_replace("@fi", date("Ymd", \strtotime("-31 days")), $fullUrl);
        $fullUrl = \str_replace("@ff", date("Ymd"), $fullUrl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Authorization:Bearer " . $tpApiKey;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        // order result array by fechaHoraCruce field
        $result = \json_decode($result, true);
        \usort($result, function ($a, $b) {
            return \strtotime($b["fechaHoraCruce"]) - \strtotime($a["fechaHoraCruce"]);
        });

        $result = \json_encode($result);

        if ($result === false) {
            return \json_encode([]);
        } else {
            return $result;
        }
    }

    public function insertdb(){
        
    }
    
}
