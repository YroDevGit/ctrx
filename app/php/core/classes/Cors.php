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
}
