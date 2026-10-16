<?php
namespace Classes;

class Apibase{

    private static array $body = [];


    public static function post(string $url){
        self::$body['url'] = $url;
        self::$body['method'] = "POST";
        return new self;
    }

    public static function put(string $url){
        self::$body['url'] = $url;
        self::$body['method'] = "PUT";
        return new self;
    }

    public static function patch(string $url){
        self::$body['url'] = $url;
        self::$body['method'] = "PATCH";
        return new self;
    }

    public static function get(string $url){
        self::$body['url'] = $url;
        self::$body['method'] = "GET";
        return new self;
    }

    public static function delete(string $url){
        self::$body['url'] = $url;
        self::$body['method'] = "DELETE";
        return new self;
    }

    public static function data(array $data){
        self::$body['data'] = $data;
        return new self;
    }

    public static function where(array|int $where){
        if(is_int($where)){
            self::$body['data'] = $where;
        }else{
            self::$body['data'] = ["id"=> $where];
        }
        return new self;
    }





}
?>