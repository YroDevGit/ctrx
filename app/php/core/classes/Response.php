<?php

namespace Classes;

class Response
{
    protected static $code = 200;
    protected static $data = null;
    protected static $count = null;
    protected static $details = null;
    protected static $errors = null;
    protected static $message = null;
    protected static $status = 200;
    protected static $text = null;
    protected static $param = [];
    protected static $reqid = null;

    static function json(array $data, int $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    static function success(string $message = "Success", array|null|bool $details = [])
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("success_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response);
    }

    static function error(string $message = "Error", array|null|bool $details = [], int $status = 500)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("error_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function failed(string $message = "Failed", array|null|bool $details = [], int $status = 200)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("failed_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function not_found(string $message = "Not found", array|null|bool $details = [], int $status = 500)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("notfound_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function forbidden(string $message = "Forbidden", array|null|bool $details = [], int $status = 500)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("forbidden_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function unauthorized(string $message = "Unauthorized", array|null|bool $details = [], int $status = 500)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("unauthorized_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function bad_request(string $message = "Bad Request", array|null|bool $details = [], int $status = 210)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("badrequest_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function warning(string|null $message = "Warning", array|null|bool $details = [], int $status = 200)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("warning_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function network_error(string|null $message = "Network error", array|null|bool $details = [], int $status = 500)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("no_internet_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function server_error(string|null $message = "Server error", array|null|bool $details = [], int $status = 500)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("backend_error_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    static function db_error(string|null $message = "Database error", array|null|bool $details = [], int $status = 500)
    {
        $details = is_array($details) ? $details : [];
        $response = [
            "code" => getenv("db_error_code"),
            "message" => $message,
            "details" => $details
        ];
        self::json($response, $status);
    }

    private static function array_data()
    {
        return ["code" => self::$code, "message" => self::$message, "details" => self::$details, "data" => self::$data, "errors" => self::$errors, "status" => self::$status];
    }

    public static function code(int $code)
    {
        self::$code = $code;
        return new self;
    }

    public static function parameter(array|null $param)
    {
        $data = is_null($param) ? [] : $param;
        self::$param = $data;
        return new self;
    }

    public static function variable(array|null $param)
    {
        return self::parameter($param);
    }
    
    public static function var(array|null $param)
    {
        return self::parameter($param);
    }

    public static function message(string|null $message)
    {
        $message = is_null($message) ? "" : $message;
        self::$message = $message;
        return new self;
    }

    public static function count(int|null $count)
    {
        $message = is_null($count) ? 0 : $count;
        self::$count = $message;
        return new self;
    }

    public static function text(mixed $text)
    {
        $text = is_null($text) ? "" : $text;
        self::$text = $text;
        return new self;
    }

    public static function details(mixed $details)
    {
        $details = is_array($details) ? $details : [];
        self::$details = $details;
        return new self;
    }

    public static function errors(mixed $errors)
    {
        $errors = is_array($errors) ? $errors : [];
        self::$errors = $errors;
        return new self;
    }

    public static function data(mixed $data)
    {
        $data = is_array($data) ? $data : [];
        self::$data = $data;
        return new self;
    }

    public static function push(int $status = 200): void
    {
        $response = [];
        $details = self::$details;
        $data = self::$data;
        $errors = self::$errors;
        $message =  self::$message;
        $count =  self::$count;
        $code = self::$code;
        $text = self::$text;
        $reqid = self::$reqid;
        $parameters = self::$param;

        if ($parameters && ! empty($parameters) && ! is_null($parameters)) {
            $response = $parameters;
        }
        if (! is_null($reqid)) {
            $response['request_id'] = $reqid;
        }
        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }
        if (! is_null($count)) {
            $response['count'] = $count;
        }
        if (! is_null($details)) {
            $response['details'] = $details;
        }
        if (! is_null($data)) {
            $response['data'] = $data;
        }
        if (! is_null($text)) {
            $response['text'] = $text;
        }
        if (! is_null($message)) {
            $response['message'] = $message;
        }
        $response['code'] = $code;
        $response = array_reverse($response);

        self::json($response, $status);
    }

    public static function pack(int $status = 200): void
    {
        self::push($status);
    }

    public static function exec(int $status = 200)
    {
        self::push($status);
    }

    public static function send(int $status = 200)
    {
        self::push($status);
    }

    public static function req_id($id = null){
        $id = $id ?? ctr_get_current_request_id();
        self::$reqid = $id;
        return new self;
    }

    public static function X(int $status = 200): void
    {
        self::push($status);
    }

    public static function dump($details = null, $status = 200){
        $response = [
            "code" => getenv("error_code"),
            "post" => postdata(),
            "get" => $_GET,
            "headers" => server_headers()
        ];
        if($details){
            $response['details'] = $details;
        }
        self::json($response, $status);
    }
}
