<?php

function test2()
{
    $API_ACCESS_KEY = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDYFopPkf6FxPJA\nZYdhodSQTCmUO1FBPZmnynDauVLrlSO/f2KlaHwQks9ub0Iubl87cvUF6Q+KhrWo\nP2WowqYCJx40jGJ0ty2T4g10w6vcAjtZhQApjUjLE9uPpGhwIR380FTsi5nJNp9a\nRP3TRdA1HnCMBQ5MXCbkNrxRkBR3ft4F1Li0Ouqa0bO2y4YCMsxWF2qFJN4Ur4z6\nyWx/7q5tmbekZWFGpOOL23IYKK72pwLOMIAcHgu/aopVoOalurAFNV9ay0joAPc0\nsSGOpBF85EsFtDG1YilOUNyjw4h+OykJdxzml3I3HaVFvoCMZmjNG1zlEBhz+uPc\nLJOkcE4fAgMBAAECggEAR1xyQsLZBj47Lm8ZHXH/K8QrOwu30s7QDqx5RpJLQweW\nragY4L03s5V4MWVGuuSySIS3TENiYYIJPc3p+aiGgFGA1SPDY/4WbC8L+JKiD98g\n0HhuzEofTwl4yeAmqVkWeSYn1ZJosB181KfSF1KH5vLtgiAtT6RjJ5y35kuppqhA\nLValiUzJXwUbnByLaqRubDdMuOD/O8G9Hspwh/wEYLcljhzY+OUdTXWM/8PNYckx\n6KNKQPTAPoWRdaMMQT24Ms3sYvDtGKwkJAzDD8EqW+CmhaVm7LVdaBr5FlzeVtpM\nhJDFgDwE1T3dwP15TKjvS/XQ3YhRqoPNzP/Wrx67GQKBgQD/73pEhocmUbGEVjjs\n32AhPC687zBHpSyaJZVBmJrDEM+uShP4ft3QDx3/yw2ekxWz7EKjof4IiKNS2xYC\nvc/QrwZgc4A//1iVf4F7wsNve8zxx00VYocz63IKx9xqpKCnj7TRVuF7ILvLMslN\nyqxMP4y018PvyLabBp1xGaBxhQKBgQDYJH2AqHPZuIfmZ+R9ukY66fXR7pmdZL+C\nzPn6+GbB8WHKXTABUj0eydCjbqc0O8tFO4zDk1fK316GTNH0u/Oz9MCcmZzo7gls\nXOCM6lUp+FmYHpjsuanbnly92nXUIKMEi/KNybj0+pnh8QsZ0jN4b7ycifQtdiDg\n78xu1dWAUwKBgQCXnZ6pCaj2ol6vLwT6Djo8dhKaCnhneQo7JxiMi4LjHApsDaZX\nB5EPuGTlK70du7SXqdawaT68f3WBmBgp95gs4AK/EK1hPDuWFLr4PiDY4lY+xPp9\nOKkvsMMWb9+7rVse6JsNiJJ7BqE5dxSZ6P65DMymNUv1uMm0fO64GZ5aoQKBgH2c\nn09RFprWUiyF/lVPFxeP9mt9tcqxzVav1yuShu15YbKge5CZAapN8TG50fRaN6TR\nmdnjXGcrbxyvsmj/ff78Y5/e6kC4bcOLKnjaionsqeztA0S4Fc3rd8xiFI4mNXcj\n+d+K9zFwHlQfqKrl7UG19jAnQD/XYf43fmB/Zye/AoGAdtr1pwWNK7pzmzeSsqra\ngdjZQvs2vv97NUel/PcTj6uqQyW9HV5+PlEfnhoQqx6DQ/oJmBXgA+qrUUTB0+cA\nXYMQU4JbeCiQb4ju9JcTdF/WX95kSB27DBV+qynzs2vuw3SUcfCWNedL5fPat3nK\nmo954zs7yoXf4R47laSrhyU=\n-----END PRIVATE KEY-----\n";

    $registrationIds = 'eBv0Qm3dSVeR0eVkq7VamW:APA91bEuqtdEP9gtN1kWZX3Ss71KwBALYkoXTfjuQ9vUWdIslRiRB2c9Wl1axVqC5f0BCIyzk8DoqC0G7u81gA-YTNgrf6vM4t8QLq4DX-Q6EZG-uTSPCltyXQUjyEr58rfGYH4bbBsN'; 
    // $_GET['id'];
    #prep the bundle
    // $msg = array(
    //     'body'  => 'que pex',
    //     'title' => 'hola',
    //     // 'icon'  => 'myicon',/*Default Icon*/
    //     // 'sound' => 'mySound'/*Default sound*/
    // );
    // $fields = array(
    //     'to'        => [$registrationIds],
    //     'notification'  => $msg
    // );

    $msg = array(
        "message" => array (
            "token" => $registrationIds,
            "notification" => array (
                "body" => "This is an FCM notification message!",
                "title" => "FCM Message"
            )
        )
    );

    $headers = array(
        'Authorization: Bearer ' . $API_ACCESS_KEY,
        'Content-Type: application/json'
    );
    #Send Reponse To FireBase Server    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/myproject-b5ae1/messages:send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($msg));
    $result = curl_exec($ch);
    curl_close($ch);
    #Echo Result Of FireBase Server
    echo $result;
}

function sendFirebaseNotification()
{
    $ApiSecret = "AIzaSyA1zGtbfO7KjBgKsJX0LB1mgqCJm9jFCSI";
    $androidAppId = "AIzaSyDaAfdKi0MPcMoYm_mjS4Vg5fgiPx_66yA";

    $url = 'http://fcm.googleapis.com/fcm/send';

    $fields = array(
        "notification" => array(
            "title" => 'hola',
            "body" => 'que pex',
            // "icon" => $image,
            // Linea original 
            //"click_action" => "http://lineaexpressapp.desarrollosenlanube.net"
            "click_action" => "https://fpfch.gob.mx/"

        ),
        "registration_ids" => $androidAppId,
        "data" => array(
            "data" => "something",
        )
    );

    $fields = json_encode($fields);
    $headers = array(
        'Authorization: key=' . $ApiSecret,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $result = curl_exec($ch);
    curl_close($ch);


    var_dump($result);
}
