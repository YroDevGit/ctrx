<?php

namespace Classes;
use DateTime;

class Date
{

    //create a function here...
    static function get_name($date, $format = "F d, Y H:i:s")
    {
        return date($format, strtotime($date));
    }

    static function change_date(string $date, string|null $interval)
    {
        $given = $date;
        $date = new DateTime($given);
        $date->modify($interval);
        ///or: $new   = date('Y-m-d H:i:s', strtotime($given . ' +20 minutes'));
        return $date->format('Y-m-d H:i:s');
    }

    static function get_date(string $date, string $format = "Y-m-d H:i:s")
    {
        $given = $date;
        $date  = new DateTime($given);
        return $date->format($format);
    }

    static function now($dateformat = "Y-m-d H:i:s")
    {
        return date($dateformat);
    }
}




/**
 * F d, Y H:i:s
 * 
 * 
 * F → Full month name

 *d → Day with leading zero

 *Y → Full year

 *h → Hour (12-hour)

 *H → Hour (24-hour)

 *i → Minutes

 *s → Seconds

 *A → AM/PM
 */
