<?php

namespace Classes;

use DateTime;
use Exception;

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

    static function timeDif(string|null $datetime, $date_local = false)
    {
        if (! $datetime) return null;
        if($date_local){
            $datetime = strval(self::local($datetime));
        }
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

    static function zone_to_datetime(string $timezone){
        $timezone = new \DateTimeZone($timezone);
        $datetime = new \DateTime('now', $timezone);
        return $datetime->format('P');
    }

    public static function datetime_to_zones(string $offset): array
    {
        $offset = str_replace(':', '', $offset);
        if (!preg_match('/^([+-])(\d{2})(\d{2})$/', $offset, $m)) {
            if (!preg_match('/^([+-])(\d{1,2})$/', $offset, $m2)) {
                return [];
            }
            $sign = $m2[1];
            $hours = (int) $m2[2];
            $minutes = 0;
        } else {
            $sign = $m[1];
            $hours = (int) $m[2];
            $minutes = (int) $m[3];
        }

        $seconds = ($hours * 3600 + $minutes * 60) * ($sign === '-' ? -1 : 1);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $matches = [];

        foreach (\DateTimeZone::listIdentifiers() as $tzName) {
            $tz = new \DateTimeZone($tzName);
            if ($tz->getOffset($now) === $seconds) {
                $matches[] = $tzName;
            }
        }

        return $matches;
    }

    public static function datetime_to_zone(string $offset, ?string $preferred = null): ?string
    {
        $matches = self::datetime_to_zones($offset);

        if ($preferred && in_array($preferred, $matches, true)) {
            return $preferred;
        }

        $priority = [
            'Asia/Manila', 'Asia/Singapore', 'Asia/Shanghai', 'Asia/Tokyo',
            'Asia/Kolkata', 'Europe/London', 'Europe/Paris',
            'America/New_York', 'America/Chicago', 'America/Denver',
            'America/Los_Angeles', 'UTC',
        ];

        foreach ($priority as $name) {
            if (in_array($name, $matches, true)) {
                return $name;
            }
        }

        return $matches[0] ?? null;
    }

    static function local($date, $timezone = null)
    {
        if(! isset($date)) return null;
        if(! $date) return null;
        $dbTimezone = $timezone ?? env('dbtimezone') ?? null;
        if(! $dbTimezone) return $date;
        if(! str_starts_with($dbTimezone, "-") && ! str_starts_with($dbTimezone, "+")){
            $dbTimezone = self::zone_to_datetime($dbTimezone);
        }
        $appTimezone = date_default_timezone_get();

        $date = new \DateTime(
            $date,
            new \DateTimeZone($dbTimezone)
        );

        $date->setTimezone(
            new \DateTimeZone($appTimezone)
        );

        $date = $date->format('Y-m-d H:i:s');

        return $date;
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