<?php

// Notify.php
// Funcion para envio de notificaciones moviles usando firebase
// LineaExpressApp

namespace Util;

class Notify
{
    public static function sendFirebaseMessage($message, $device_id)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $firebaseApiKey = $_ENV["FIREBASE_API_KEY"];

        $fields = array(
                'registration_ids' => array(
                        $device_id
                ),
                'notification' => array(
                        "body" => $message,
                        "title" => \Util\Mail::getAppName(),
                )
        );
        $fields = json_encode($fields);

        $headers = array(
                'Authorization: key=' . $firebaseApiKey,
                'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        $result = curl_exec($ch);
        // echo $result;
        curl_close($ch);
    }
}
