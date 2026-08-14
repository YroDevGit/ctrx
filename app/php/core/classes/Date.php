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

    static function timeDif(string|null $datetime)
    {
        if(! $datetime) return null;
        $time = strtotime($datetime);
        $now  = time();

        $diff = $time - $now;
        $absDiff = abs($diff);

        if ($absDiff < 60) {
            $value = $absDiff;
            $unit = 'second';
        } elseif ($absDiff < 3600) {
            $value = floor($absDiff / 60);
            $unit = 'minute';
        } elseif ($absDiff < 86400) {
            $value = floor($absDiff / 3600);
            $unit = 'hour';
        } elseif ($absDiff < 604800) {
            $value = floor($absDiff / 86400);
            $unit = 'day';
        } elseif ($absDiff < 2592000) {
            $value = floor($absDiff / 604800);
            $unit = 'week';
        } elseif ($absDiff < 31536000) {
            $value = floor($absDiff / 2592000);
            $unit = 'month';
        } else {
            $value = floor($absDiff / 31536000);
            $unit = 'year';
        }

        $unit .= $value != 1 ? 's' : '';

        return $diff < 0
            ? "$value $unit ago"
            : "$value $unit left";
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