<?php

// SMSApi.php
// Funciones para envio de SMS y Whatsapp usando SMSMasivos
// LineaExpressApp

class SMSApi
{


    public static function sendSMS(string $phone_number, string $message, string $cc): bool
    {
        if ($cc != '52') {
            return true;
        }

        // removel all non-numeric characters from $phone_number and $cc
        $phone_number = preg_replace("/[^0-9]/", "", $phone_number);
        $cc = preg_replace("/[^0-9]/", "", $cc);

        if ($cc != '52') {
            return self::sendWhatsappMessage($phone_number, $message, $cc);
        }

        $res = false;
       
        // inicio de api de sofmex
        $url = 'https://sofmex.com/api/sms/v3/asignacion';

        $headers = array(
         'Content-Type: application/json',
         'Authorization: Bearer eyJhbGciOiJIUzUxMiJ9.eyJhdXRob3JpdGllcyI6Ilt7XCJhdXRob3JpdHlcIjpcIk1FTlNBSkVcIn1dIiwic3ViIjoiYWlwb25jZUBlbW9iaWxlLmNvbS5teCIsImlhdCI6MTcwNjM3ODEzOCwiZXhwIjo0ODU5OTc4MTM4fQ.5WW8SSqrOhif4vNo-GM_nSqNw2ZJ37cDoYQdlgoL5A00s6A69P-N3fCMGcNL01p_VaW83eVRrS2F-tddBON6pg',
            );

            $data = array(
            'registros' => array(
                 array(
            'mensaje' => $message,
            'telefono' => $phone_number,
            'id' => 1,
             ),
                ),
                );

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                echo 'Error al realizar la solicitud cURL: ' . curl_error($ch);
                }

                curl_close($ch);

                echo $response;
                
        //fin api de sofmex
    }
    

    public static function sendWhatsappMessage(string $phone_number, string $message, string $cc): bool
    {
		if ($cc != '52') {
            return true;
        }

        $url = 'https://sofmex.com/api/sms/v3/asignacion';

        $headers = array(
         'Content-Type: application/json',
         'Authorization: Bearer eyJhbGciOiJIUzUxMiJ9.eyJhdXRob3JpdGllcyI6Ilt7XCJhdXRob3JpdHlcIjpcIk1FTlNBSkVcIn1dIiwic3ViIjoiYWlwb25jZUBlbW9iaWxlLmNvbS5teCIsImlhdCI6MTcwNjM3ODEzOCwiZXhwIjo0ODU5OTc4MTM4fQ.5WW8SSqrOhif4vNo-GM_nSqNw2ZJ37cDoYQdlgoL5A00s6A69P-N3fCMGcNL01p_VaW83eVRrS2F-tddBON6pg',
            );

            $data = array(
            'registros' => array(
                 array(
            'mensaje' => $message,
            'telefono' => $phone_number,
            'id' => 1,
             ),
                ),
                );

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                echo 'Error al realizar la solicitud cURL: ' . curl_error($ch);
                }

                curl_close($ch);

                echo $response;
        //fin api de sofmex



    }

    public static function sendSMSusa(string $phone_number, string $message, string $cc="52"): bool
    {
        if ($cc != '52') {
            return true;
        }

        // removel all non-numeric characters from $phone_number and $cc
        $phone_number = preg_replace("/[^0-9]/", "", $phone_number);
        $cc = preg_replace("/[^0-9]/", "", $cc);

        if ($cc != '52') {
            return self::sendWhatsappMessageusa($phone_number, $message, $cc);
        }

        $res = false;
       
        // inicio de api de sofmex
        $url = 'https://sofmex.com/api/sms/v3/asignacion';

        $headers = array(
         'Content-Type: application/json',
         'Authorization: Bearer eyJhbGciOiJIUzUxMiJ9.eyJhdXRob3JpdGllcyI6Ilt7XCJhdXRob3JpdHlcIjpcIk1FTlNBSkVcIn1dIiwic3ViIjoiYWlwb25jZUBlbW9iaWxlLmNvbS5teCIsImlhdCI6MTcwNjM3ODEzOCwiZXhwIjo0ODU5OTc4MTM4fQ.5WW8SSqrOhif4vNo-GM_nSqNw2ZJ37cDoYQdlgoL5A00s6A69P-N3fCMGcNL01p_VaW83eVRrS2F-tddBON6pg',
            );

            $data = array(
            'registros' => array(
                 array(
            'mensaje' => $message,
            'telefono' => "+1" . $phone_number,
            'id' => 1,
             ),
                ),
                );

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                echo 'Error al realizar la solicitud cURL: ' . curl_error($ch);
                }

                curl_close($ch);

                echo $response;
                
        //fin api de sofmex
    }
    public static function sendWhatsappMessageusa(string $phone_number, string $message, string $cc="52"): bool
    {
		if ($cc != '52') {
            return true;
        }

        $url = 'https://sofmex.com/api/sms/v3/asignacion';

        $headers = array(
         'Content-Type: application/json',
         'Authorization: Bearer eyJhbGciOiJIUzUxMiJ9.eyJhdXRob3JpdGllcyI6Ilt7XCJhdXRob3JpdHlcIjpcIk1FTlNBSkVcIn1dIiwic3ViIjoiYWlwb25jZUBlbW9iaWxlLmNvbS5teCIsImlhdCI6MTcwNjM3ODEzOCwiZXhwIjo0ODU5OTc4MTM4fQ.5WW8SSqrOhif4vNo-GM_nSqNw2ZJ37cDoYQdlgoL5A00s6A69P-N3fCMGcNL01p_VaW83eVRrS2F-tddBON6pg',
            );

            $data = array(
            'registros' => array(
                 array(
            'mensaje' => $message,
            'telefono' => "+1" . $phone_number,
            'id' => 1,
             ),
                ),
                );

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                echo 'Error al realizar la solicitud cURL: ' . curl_error($ch);
                }

                curl_close($ch);

                echo $response;
        //fin api de sofmex



    }
    
}