<?php

namespace Classes;

class Cors
{


    public static function allow_origin(array|null $allowed, callable $error)
    {
        $allowed = is_null($allowed) ? [] : $allowed;

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origin === '' || in_array($origin, $allowed) || $origin == rootpath) {
        } else {
            $error($origin);
            return;
        }

        //header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        //header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    public static function set_request_method($method)
    {
        set_request_method($method);
    }

    public static function set_allowed_origin(array|string $origins)
    {
        $key = "ctrx_req_allowed_origin_sao";
        if (is_array($origins)) {
            \Classes\Ccookie::add($key, $origins, 100);
        } else {
            $data = \Classes\Ccookie::get($key);
            $arr = [];
            if (! $data) {
                $arr[] = $origins;
            } else {
                $arr = [...$data];
                $arr[] = $origins;
            }
            \Classes\Ccookie::add($key, $arr);
        }
    }

    public static function get_allowed_origin(string $structure = "array")
    {
        $data = \Classes\Ccookie::get("ctrx_req_allowed_origin_sao");
        if ($structure == "array") {
            if (! $data) return [];
            return $data;
        }
        else if($structure == "string"){
            if(! $data) return null;
            return implode(",", $data);
        }else{
            return null;
        }
    }
}
