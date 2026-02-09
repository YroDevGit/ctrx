<?php

namespace Classes;

use Exception;
use DateTime;
use DateTimeZone;
class TimeZone
{

    //create a function here...

    static function set_default_timezone(string|null $timezone = "Asia/Manila")
    {
        return date_default_timezone_set($timezone);
    }

    static function get_default_timezone()
    {
        return date_default_timezone_get();
    }

    static function convert(string $to, string $date = "now", string|null $from = null, $format = "Y-m-d H:i:s"):string
    {
        $from ??= date_default_timezone_get();

        try {
            $dt = new DateTime($date, new DateTimeZone($from));
            $dt->setTimezone(new DateTimeZone($to));
            return $dt->format($format);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
