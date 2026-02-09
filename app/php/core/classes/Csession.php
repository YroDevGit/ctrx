<?php

namespace Classes;

use Exception;

class Csession
{
    public static $errmsg = null;

    //create a function here...
    public static function add(string $key, mixed $value)
    {
        if (isset($_SESSION[$key])) {
            throw new Exception("Session $key already exist");
        }
        self::array_push($key, $value);
    }

    public static function set(string $key, mixed $value)
    {
        self::array_push($key, $value);
    }

    public static function exist(string $key): bool
    {
        if (isset($_SESSION[$key])) {
            return true;
        }
        return false;
    }

    public static function get(string $key, bool $strick = false)
    {
        if (! isset($_SESSION[$key])) {
            if ($strick) {
                throw new Exception("Session $key not found.!");
            } else {
                return null;
            }
        }
        $session = $_SESSION[$key];
        $ret = json_decode($session, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $ret;
        } else {
            $post = $session;
            return $post;
        }
    }


    public static function setError(string $err)
    {
        self::$errmsg = $err;
    }

    public static function validate(string $key, $errorpage = null, $redirect = false)
    {
        if (! isset($_SESSION[$key])) {
            if (! $errorpage) {
                $erms = self::$errmsg;
                die($erms ?? "Session Validation Failed");
            } else {
                if ($redirect) {
                    redirect($errorpage);
                    exit;
                } else {
                    $bee = substr($errorpage, -4) == ".php" ? $errorpage : $errorpage . ".php";
                    include fe_page . "/" . $bee;
                }
            }
        }
    }

    public static function remove(string $key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return true;
    }

    private static function array_push(string $key, mixed $value)
    {
        if (is_array($value)) {
            $_SESSION[$key] = json_encode($value);
        }
        $_SESSION[$key] = $value;
    }
}
