<?php

function logErr($data)
{
    $logPath = "/tmp/stripelog.txt";
    $mode = (!file_exists($logPath)) ? 'w' : 'a';
    $logfile = fopen($logPath, $mode);
    fwrite($logfile, "\r\n" . $data);
    fclose($logfile);
}

$req_dump = $_REQUEST;
logErr("request data:\n" . $req_dump);

$body = file_get_contents('php://input');
logErr("request body: \n" . $body);

echo "Respuesta de stripe recibida:<br/>";

var_dump($req_dump);

