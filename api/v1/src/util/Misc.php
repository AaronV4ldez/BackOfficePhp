<?php

// Misc.php
// Funciones utilitarias para majeo de fecha/hora
// LineaExpressApp

namespace Util;

class Misc
{
    public static function isValidISODate($dateStr) {
        if (preg_match('/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/', $dateStr) > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public static function isValid24hTime($timeStr) {
        if (preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/", $timeStr) > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

}