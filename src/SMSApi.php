<?php

class SMSApi
{
    public function sendSMS(string $phone_number, string $message): bool
    {
        $res = false;

        $params = array(
            /*"mensaje" => $message,
            "telefono" => $phone_number,
            //"country_code" => "52",
            "id" => "1"*/
            'registros' => array(
                array(
                    'mensaje' => $message,
                    'telefono' => $phone_number,
                    'id' => 1
                )
            )
        );
        $jsonParams = json_encode($params);
        $headers = array(
            "apikey: " . $_ENV["SMS_API_KEY"]
        );
        curl_setopt_array($ch = curl_init(), array(
            CURLOPT_URL => "https://sofmex.com/api/sms/v3/asignacion",
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => 1,
            //CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_POSTFIELDS => $jsonParams,
            CURLOPT_RETURNTRANSFER => 1
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        // echo $response;

        $data = json_decode($response, true);

        $res = $data["success"];
        return $res;
    }

    public function sendWhatsappMessage(): void
    {
    }
}
