<?php

namespace Classes;

use Exception;
use DateTime;
use DateTimeZone;

class TimeZone
{

    //create a function here...

    static function set_default_timezone(string|null $timezone = "Asia/Manila", bool $global = true)
    {
        if(! \Classes\Ctrx::__isValidTzone($timezone)) return;
        if($global){
            setcookie("__ctrx_server_timezone", $timezone, time() + 10800, "/", "", !empty($_SERVER['HTTPS']));
        }
        return date_default_timezone_set($timezone);
    }

    static function is_valid(string $timezone){
        return \Classes\Ctrx::__isValidTzone($timezone);
    }

    static function get_default_timezone(bool $global = true)
    {
        if($global){
            $myTzone = $_COOKIE['__ctrx_server_timezone'] ?? null;
            if($myTzone && \Classes\Ctrx::__isValidTzone($myTzone)){
                return $myTzone;
            }
        }
        return date_default_timezone_get();
    }

    static function convert(string $to, string $date = "now", string|null $from = null, $format = "Y-m-d H:i:s"): string
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

    function convert_timezone($datetime, $timezone = 'Asia/Manila', $format = "Y-m-d H:i:s")
    {
        $date = new DateTime($datetime, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($timezone));

        return $date->format($format);
    }
}