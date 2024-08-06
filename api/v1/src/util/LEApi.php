<?php

// LEApi.php
// Funciones para interactuar con la API de Controles-LineaExpress
// LineaExpressApp

namespace Util;

use PDO;
use PDOException;

const LE_API_URL_USUARIO = "/Usuarios/"; // + sentri/email
const LE_API_URL_CARS_CROSS = "/Cruces/@idu/vehiculos/@idv/Cruces/@fi/@ff";
const LE_API_URL_RECARGA = "/Transacciones/@idu/vehiculos/@idv";
const LE_API_URL_CARS_CROSS_NEW = "/ConsultaCrucesTag/@tag/@fi/@ff";
//const LE_API_URL_CARS_CROSS_NEW = "/ConsultaCrucesTag/@tag/20240601/20240701";


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
        $fullUrlTemplate = $baseURL . LE_API_URL_CARS_CROSS_NEW;
    
        $allResults = [];
    
        // Definir los diferentes rangos de fechas para las búsquedas
        $dateRanges = [
            ['fi' => date("Ymd", strtotime("-31 days")), 'ff' => date("Ymd")],
            ['fi' => date("Ymd", strtotime("-62 days")), 'ff' => date("Ymd", strtotime("-31 days"))],
            ['fi' => date("Ymd", strtotime("-93 days")), 'ff' => date("Ymd", strtotime("-62 days"))],
            ['fi' => date("Ymd", strtotime("-124 days")), 'ff' => date("Ymd", strtotime("-93 days"))],
            ['fi' => date("Ymd", strtotime("-155 days")), 'ff' => date("Ymd", strtotime("-124 days"))],
            ['fi' => date("Ymd", strtotime("-186 days")), 'ff' => date("Ymd", strtotime("-155 days"))],
            ['fi' => date("Ymd", strtotime("-217 days")), 'ff' => date("Ymd", strtotime("-186 days"))],
            ['fi' => date("Ymd", strtotime("-248 days")), 'ff' => date("Ymd", strtotime("-217 days"))],
            ['fi' => date("Ymd", strtotime("-279 days")), 'ff' => date("Ymd", strtotime("-248 days"))],
            ['fi' => date("Ymd", strtotime("-310 days")), 'ff' => date("Ymd", strtotime("-279 days"))],
            ['fi' => date("Ymd", strtotime("-341 days")), 'ff' => date("Ymd", strtotime("-310 days"))],
            ['fi' => date("Ymd", strtotime("-372 days")), 'ff' => date("Ymd", strtotime("-341 days"))],
            ['fi' => date("Ymd", strtotime("-403 days")), 'ff' => date("Ymd", strtotime("-372 days"))],
            ['fi' => date("Ymd", strtotime("-434 days")), 'ff' => date("Ymd", strtotime("-403 days"))]
        ];
    
        // Array asociativo para evitar duplicados
        $uniqueResults = [];
    
        foreach ($dateRanges as $index => $range) {
            $fullUrl = \str_replace("@tag", $tag, $fullUrlTemplate);
            $fullUrl = \str_replace("@fi", $range['fi'], $fullUrl);
            $fullUrl = \str_replace("@ff", $range['ff'], $fullUrl);
    
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
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
    
            // Verificar si la solicitud fue exitosa
            if ($httpCode == 200) {
                $result = \json_decode($result, true);
                if ($result !== null) {
                    foreach ($result as $record) {
                        // Crear una clave única para cada registro basado en idOperador y fechaHoraCruce
                        $uniqueKey = $record['fechaHoraCruce'];
                        // Verificar si ya existe en el array asociativo
                        if (!isset($uniqueResults[$uniqueKey])) {
                            $uniqueResults[$uniqueKey] = $record;
                        }
                    }
                } else {
                    error_log("Error decoding JSON response for range index $index: " . json_last_error_msg());
                }
            } else {
                error_log("Error fetching data for range index $index: HTTP $httpCode");
            }
        }
    
        // Convertir el array asociativo a un array simple
        $allResults = array_values($uniqueResults);
    
        // Insertar los registros únicos en la base de datos
        foreach ($allResults as $record) {
            self::insertdb($record);
        }
    
        // Devolver todos los resultados en un solo JSON
        header('Content-Type: application/json');
        echo json_encode($allResults, JSON_PRETTY_PRINT);
    }


    public static function insertdb($record)
    {
        try {
            $dsn = 'mysql:host=127.0.0.1;dbname=liex;charset=utf8';
            $username = 'liex_user';
            $password = 'line@infernal656';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, $username, $password, $options);

            // Verificar si los datos ya existen
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM historical WHERE idOperador = :idOperador AND fechaHoraCruce = :fechaHoraCruce");
            $stmt->bindParam(':idOperador', $record['idOperador'], PDO::PARAM_INT);
            $stmt->bindParam(':fechaHoraCruce', $record['fechaHoraCruce'], PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Insertar si no existe
                $stmt = $pdo->prepare("INSERT INTO historical (idOperador, nombreOperador, siglasOperador, clavePlaza, nombrePlaza, descripcionPlaza, tipoTransito, numeroCarril, fechaHoraCruce, montoTarifa, numeroTag, claseVehiculo, tipoVehiculo) VALUES (:idOperador, :nombreOperador, :siglasOperador, :clavePlaza, :nombrePlaza, :descripcionPlaza, :tipoTransito, :numeroCarril, :fechaHoraCruce, :montoTarifa, :numeroTag, :claseVehiculo, :tipoVehiculo)");
                $stmt->bindParam(':idOperador', $record['idOperador'], PDO::PARAM_INT);
                $stmt->bindParam(':nombreOperador', $record['nombreOperador'], PDO::PARAM_STR);
                $stmt->bindParam(':siglasOperador', $record['siglasOperador'], PDO::PARAM_STR);
                $stmt->bindParam(':clavePlaza', $record['clavePlaza'], PDO::PARAM_STR);
                $stmt->bindParam(':nombrePlaza', $record['nombrePlaza'], PDO::PARAM_STR);
                $stmt->bindParam(':descripcionPlaza', $record['descripcionPlaza'], PDO::PARAM_STR);
                $stmt->bindParam(':tipoTransito', $record['tipoTransito'], PDO::PARAM_STR);
                $stmt->bindParam(':numeroCarril', $record['numeroCarril'], PDO::PARAM_INT);
                $stmt->bindParam(':fechaHoraCruce', $record['fechaHoraCruce'], PDO::PARAM_STR);
                $stmt->bindParam(':montoTarifa', $record['montoTarifa'], PDO::PARAM_INT);
                $stmt->bindParam(':numeroTag', $record['numeroTag'], PDO::PARAM_STR);
                $stmt->bindParam(':claseVehiculo', $record['claseVehiculo'], PDO::PARAM_STR);
                $stmt->bindParam(':tipoVehiculo', $record['tipoVehiculo'], PDO::PARAM_STR);
                $stmt->execute();

                //echo "Data inserted successfully!";
            } else {
                //echo "Data already exists in the database.";
            }
        } catch (PDOException $e) {
            //echo "Error: " . $e->getMessage();
        }
    }

    
}
