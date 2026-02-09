<?php

namespace Classes;

class Currency
{

    private static $currencies = [
        "USD" => "$",
        "EUR" => "€",
        "GBP" => "£",
        "JPY" => "¥",
        "PHP" => "₱",
        "CNY" => "¥",
        "KRW" => "₩",
        "INR" => "₹",
        "AUD" => "A$",
        "CAD" => "C$",
    ];

    private static function format($code, $amount = null)
    {
        $symbol = self::$currencies[$code] ?? "";
        if ($amount === null) {
            return $symbol;
        }
        return $symbol . number_format($amount, 2);
    }

    public static function usd($amount = null)
    {
        return self::format("USD", $amount);
    }

    public static function eur($amount = null)
    {
        return self::format("EUR", $amount);
    }

    public static function gbp($amount = null)
    {
        return self::format("GBP", $amount);
    }

    public static function yen($amount = null) // alias for JPY
    {
        return self::format("JPY", $amount);
    }

    public static function peso($amount = null) // PHP Peso
    {
        return self::format("PHP", $amount);
    }

    public static function cny($amount = null)
    {
        return self::format("CNY", $amount);
    }

    public static function krw($amount = null)
    {
        return self::format("KRW", $amount);
    }

    public static function inr($amount = null)
    {
        return self::format("INR", $amount);
    }

    public static function aud($amount = null)
    {
        return self::format("AUD", $amount);
    }

    public static function cad($amount = null)
    {
        return self::format("CAD", $amount);
    }
}
