<?php

namespace Classes;

use Classes\Ccookie;
use Classes\Random;

class Ctrql
{

    private static $ctrqlString = "ctrql_cookie_cuser";
    private static $expiration = (60 * 24) * 7;

    //create a function here...

    public static function activate(string|array $access = "CRUDMQ", int $minutes = 60)
    {
        $ctrqlString = self::$ctrqlString;
        $ccookie = Ccookie::exist($ctrqlString);

        if (is_array($access)) {
            $imp = implode("", $access);
            $access = $imp;
        }
        $access = strtoupper($access);
        if (! $ccookie) {
            Ccookie::add($ctrqlString, $access, $minutes);
        }
        Ccookie::delete($ctrqlString);
        Ccookie::add($ctrqlString, $access, $minutes);
        self::set_expiration($minutes);
        return true;
    }

    public static function isActive():bool{
        $ctrqlString = self::$ctrqlString;
        $ccookie = Ccookie::exist($ctrqlString);
        if($ccookie) return true;
        return false;
    }

    public static function remove()
    {
        $ctrqlString = self::$ctrqlString;
        $ccookie = Ccookie::exist($ctrqlString);
        if (! $ccookie) {
            return false;
        }
        Ccookie::delete($ctrqlString);
        self::refresh_table_filter();
        return true;
    }

    public static function disable()
    {
        self::remove();
    }

    public static function getAccess(): array
    {
        $ctrqlString = self::$ctrqlString;
        $ccookie = Ccookie::exist($ctrqlString);
        if (! $ccookie) {
            return [];
        }
        $cok = Ccookie::get($ctrqlString);

        $arr = str_split($cok);
        if (! $arr) {
            return [];
        }
        return $arr;
    }

    public static function checkAccess(string $access)
    {
        $access = strtoupper($access);
        $arr = self::getAccess();
        if (in_array($access, $arr)) {
            return true;
        }
        return false;
    }

    public static function accept_table(array $tables)
    {
        $minutes = self::get_expiration();
        $ctrqlString = self::$ctrqlString . "_at";
        $ccookie = Ccookie::exist($ctrqlString);
        if (! is_array($tables)) {
            return "tables should be an array.!";
        }
        if (array_is_list($tables)) {
            return "tables should has key and value.!";
        }
        $tbl = json_encode($tables);
        if (! $ccookie) {
            Ccookie::add($tbl, $tbl, $minutes);
        }
        Ccookie::delete($ctrqlString);
        Ccookie::add($ctrqlString, $tbl, $minutes);
        return true;
    }

    public static function remove_accept_table_filter()
    {
        $ctrqlString = self::$ctrqlString . "_at";
        $ccookie = Ccookie::exist($ctrqlString);
        if ($ccookie) {
            Ccookie::delete($ctrqlString);
            return true;
        }
    }

    public static function remove_ignore_table_filter()
    {
        $ctrqlString = self::$ctrqlString . "_it";
        $ccookie = Ccookie::exist($ctrqlString);
        if ($ccookie) {
            Ccookie::delete($ctrqlString);
            return true;
        }
    }

    public static function refresh_table_filter()
    {
        $ctrqlString = self::$ctrqlString . "_ex";
        $ctrqlString1 = self::$ctrqlString . "_ht";
        self::remove_accept_table_filter();
        self::remove_ignore_table_filter();
        Ccookie::delete($ctrqlString);
        Ccookie::delete($ctrqlString1);
        return true;
    }

    private static function set_expiration(int $minutes)
    {
        $ctrqlString = self::$ctrqlString . "_ex";
        Ccookie::add($ctrqlString, strval($minutes), $minutes * 2);
    }

    private static function get_expiration()
    {
        $ctrqlString = self::$ctrqlString . "_ex";
        $exist = Ccookie::exist($ctrqlString);
        if (! $exist) {
            return 1;
        }
        return intval(Ccookie::get($ctrqlString));
    }

    public static function ignore_table(array $tables)
    {
        $minutes = self::get_expiration();
        $ctrqlString = self::$ctrqlString . "_it";
        $ccookie = Ccookie::exist($ctrqlString);
        if (! is_array($tables)) {
            return "tables should be an array.!";
        }
        if (array_is_list($tables)) {
            return "tables should has key and value.!";
        }
        $tbl = json_encode($tables);
        if (! $ccookie) {
            Ccookie::add($tbl, $tbl, $minutes);
        }
        Ccookie::delete($ctrqlString);
        Ccookie::add($ctrqlString, $tbl, $minutes);
        return true;
    }


    public static function hide_table_columns(array $tables)
    {
        $minutes = self::get_expiration();
        $ctrqlString = self::$ctrqlString . "_ht";
        $ccookie = Ccookie::exist($ctrqlString);
        if (! is_array($tables)) {
            return "tables should be an array.!";
        }
        if (array_is_list($tables)) {
            return "tables should has key and value.!";
        }
        $tbl = $tables;
        if (! $ccookie) {
            Ccookie::add($ctrqlString, $tbl, $minutes);
        }
        Ccookie::delete($ctrqlString);
        Ccookie::add($ctrqlString, $tbl, $minutes);
        return true;
    }

    public static function filterAction($action)
    {
        $arr = [
            "INSERT" => "C",
            "CREATE" => "C",
            "READ" => "R",
            "SELECT" => "R",
            "GET" => "R",
            "FIND" => "R",
            "FINDONE" => "R",
            "DELETE" => "D",
            "UPDATE" => "U",
        ];

        if (! array_key_exists($action, $arr)) {
            return null;
        }
        return $arr[$action];
    }

    public static function check_table(string|null $table, string|null $action)
    {
        if (! $table || ! $action) {
            return false;
        }
        $accept = Ccookie::get(self::$ctrqlString . "_at");
        $ignore = Ccookie::get(self::$ctrqlString . "_it");

        if ($ignore && array_key_exists($table, $ignore)) {
            $role = strtoupper($ignore[$table]);
            $exp = explode("||", $role);
            $role = $exp[0] ?? "";
            $hide = $exp[1] ?? "";
            if ($role == "*") {
                $role = "CRUD";
            }
            $action = strtoupper($action);
            $access = strtoupper(self::filterAction($action));
            if (str_contains($role, $access)) {
                Response::code(unauthorized_code)->data(["role" => $role, "acc" => $access])->message("ctrql: User is not able to $action data @ '$table' table")->send(unauthorized_code);
            }
        }

        if ($accept && array_key_exists($table, $accept)) {
            $role = strtoupper($accept[$table]);
            $exp = explode("||", $role);
            $role = $exp[0] ?? "";
            $hide = $exp[1] ?? "";
            if ($role == "*") {
                $role = "CRUD";
            }
            $action = strtoupper($action);
            $access = strtoupper(self::filterAction($action));
            if (! str_contains($role, $access)) {
                Response::code(unauthorized_code)->data($accept)->message("ctrql: User is not able to $action data @ '$table' table")->send(unauthorized_code);
            }
        }
    }

    public static function get_hidden_table_columns(string|null $table)
    {
        $ctrqlString = self::$ctrqlString . "_ht";
        $ccookie = Ccookie::exist($ctrqlString);
        if (! $table) return false;
        if (! $ccookie) return false;

        $cookie = Ccookie::get($ctrqlString);

        foreach ($cookie as $k => $v) {
            $key = strtolower($k);
            $table = strtolower($table);
            if ($key == $table) {
                $arr = $cookie[$table];
                $ret = explode(",", $arr);
                return $ret;
            }else{
                continue;
            }
        }
        return [];
    }
}
