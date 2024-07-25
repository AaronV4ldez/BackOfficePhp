<?php

namespace Util;

class Logger
{
    public static function log($data)
    {
        $dateTimeStr = date('Y-m-d H:i:s');
        $trace = debug_backtrace();
        $traceStr = '';
        if (!empty($trace)) {
            foreach ($trace as $t) {
                if (isset($t[ 'file' ])) {
                    $file = $t[ 'file' ];
                    $line = $t[ 'line' ];
                    $traceStr = $traceStr . "$file:$line\r\n";
                }
            }
            $data = "$dateTimeStr\r\n$traceStr\r\n$data";
        } else {
            $data = "$dateTimeStr\r\n$data";
        }

        $logPath = "/tmp/apilog.txt";
        $mode = (!file_exists($logPath)) ? 'w' : 'a';
        $logfile = fopen($logPath, $mode);
        fwrite($logfile, $data . "\r\n----------------------------------------\r\n");
        fclose($logfile);
    }

}