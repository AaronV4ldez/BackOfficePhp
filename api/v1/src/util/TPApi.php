<?php

// TPApi.php
// Funciones para comunicacion con API de Controles-telepeaje
// LineaExpressApp

namespace Util;

const TP_API_URL_SALDO = '/' . 'ConsultaSaldoTag' . '/'; // + {tag}
const TP_API_URL_RECARGA = '/' . 'RecargaTag'; // + {tag}/{cantidad}

class TPApi
{
    public static function consultaSaldo($tag)
    {
        $tpApiKey = $_ENV["TP_API_KEY"];
        $baseURL = $_ENV["TP_BASE_URL"];
        $fullURL = $baseURL . TP_API_URL_SALDO . $tag;
        // echo $fullURL . "\n";
        // echo $tpApiKey . "\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Authorization:Bearer " . $tpApiKey;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        // $result = json_decode($result, true);
        // $result = \json_encode($result, JSON_PRETTY_PRINT);
        // echo $result;
        return $result;
    }


    // Metodos de pago:
    // 2 Efectivo
    // 3 Tarjeta de Débito Nacional
    // 4 Tarjeta de Crédito Nacional
    // 5 Tarjeta de Débito Internacional
    // 6 Tarjeta de Crédito Internacional
    // 7 Cheque
    // 8 Depósito Bancario
    // 9 Transferencia Electrónica
    // 10 Fondo en Garantía
    public static function recargaSaldo($tag, $cant, $folio, $idmetodopago = 3)
    {
        $tpApiKey = $_ENV["TP_API_KEY"];
        $baseURL = $_ENV["TP_BASE_URL"];
        $fullURL = $baseURL . TP_API_URL_RECARGA;

        // echo $fullURL . "\n";


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
            if ($value == $cant) {
                $colreal = $key;
                break;
            }
        }

        if ($colreal != "") {
            $cant = $cf[$colreal . "_r"];
        } 
        // ------------------------------ fin de cambio para sacar valor de "asignacion"

        $params = [
            "noTag" => $tag,
            "monto" => $cant,
            "idMetodoPago" => $idmetodopago,
            "folioPago"=> $folio,
        ];
        $postdata = json_encode($params);
        // echo $postdata . "\n";

        \error_log("liex controles TP: parametros de la recarga: " . \var_export($params, true) . " URL: " . $fullURL);

        // return "test tp";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Authorization:Bearer " . $tpApiKey;
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        // echo \var_export($result, true) . "\n";

        return \json_decode($result, true);
    }
}
