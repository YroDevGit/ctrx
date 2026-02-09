<?php

namespace Classes;

class Ccookie
{

    //create a function here...

    public static function add(string $key, mixed $value, int|float $minute = 60): bool
    {
        $newHour = 60 * $minute;
        if (is_array($value)) {
            $val = encrypt(json_encode($value));
            setcookie($key, $val, time() + $newHour, "/", "", isset($_SERVER['HTTPS']));
            $_COOKIE[$key] = $val;
            return true;
        } else {
            $val = encrypt($value);
            setcookie($key, $val, time() + $newHour, "/");
            $_COOKIE[$key] = $val;
            return true;
        }
        return false;
    }

    public static function add_all(array|null $data, int|float $minute = 60)
    {
        if (! $data) {
            return false;
        }
        foreach ($data as $k => $v) {
            self::add($k, $v, $minute);
        }
        return true;
    }

    public static function delete_more(array|null|string $data)
    {
        if (! $data) {
            return false;
        }
        if (is_array($data)) {
            $all = $data;
            foreach ($all as $k => $v) {
                self::delete($v);
                return true;
            }
            return true;
        }
        if (is_string($data)) {
            if ($data == "*") {
                $all = $_COOKIE;
                foreach ($all as $k => $v) {
                    self::delete($k);
                    return true;
                }
                return true;
            }
        }
        return false;
    }

    public static function delete(string $key): bool
    {
        if ($key == "*") {
            return self::delete_more("*");
        }
        setcookie($key, "", time() - 3600, "/", "", isset($_SERVER['HTTPS']));
        unset($_COOKIE[$key]);
        return true;
    }

    public static function exist(string $key): bool
    {
        if (isset($_COOKIE[$key])) {
            return true;
        }
        return false;
    }

    public static function validate_cookie(string $key, string $needle, bool $strict = false): bool
    {
        if (! self::exist($key)) return false;

        $data = self::get($key);
        if (! $data) return false;
        if (! $strict) {
            if ($needle == $data) return true;
            else return false;
        } else {
            if ($needle === $data) return true;
            else return false;
        }
    }

    public static function get(string $key)
    {
        if (isset($_COOKIE[$key])) {
            $cookie = decrypt($_COOKIE[$key]);
            $ret = json_decode($cookie, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $ret;
            } else {
                $post = $cookie;
                return $post;
            }
        }
        return null;
    }
}
