<?php
//CodeYro
function encrypt($data, string|null $key = null)
{
    return \Classes\Ctrx::encrypt($data, $key);
}

function decrypt($encrypted_data, string|null $key = null)
{
    return \Classes\Ctrx::decrypt($encrypted_data, $key);
}


function load_auto(string ...$auto)
{
    $ep = ctrx_endpoint();
    $path = "app/auto/";
    if ($ep == "FE") $path = "views/app/auto/";
    foreach ($auto as $k => $v) {
        $vv = append_php($v);
        include_once $path . $vv;
    }
}

function load_php(string ...$auto)
{
    foreach ($auto as $k => $v) {
        $vv = append_php($v);
        include_once "views/app/php/" . $vv;
    }
}

function role_has_access(string $route, $role = null)
{
    return \Classes\Ctrx::role_has_access($route, $role);
}

function cleanPath(string $path)
{
    $path = trim($path, "*");
    $path = trim($path, " /\\");
    $path = trim($path, "//");
    $path = trim($path, "\\\\");
    return $path;
}

function BasixsErrorException($e, $bee, string $errorcode = "backend_error_code")
{
    $arr = ["1", "2", "3", "4", "5", "6", "7", "8", "9"];
    shuffle($arr);
    $trace = $e->getTrace();
    $myerror = [];
    foreach ($trace as $t) {
        if (isset($t['file'])) {
            if (str_contains($t['file'], "_routes")) {
                $exp = explode("_routes", $t['file']);
                $myerror['backend'] = basixs_php_rem($exp[1] ?? "");
                $myerror['file'] = $t['file'] ?? "";
                $myerror['class'] = $t['class'] ?? "";
                $myerror['function'] = $t['function'] ?? "";
                $myerror['line'] = $t['line'] ?? "";
                break;
            }
        }
    }
    $traceString = $e->getTraceAsString();
    $line = $e->getLine();
    $file = $e->getFile();
    $message = $e->getMessage();
    $hascode = ctr_get_current_request_id();
    $code = $e->getCode();
    $getMessage = json_encode($trace);
    $cmsg = $message . " at line " . ($myerror['line'] ?? $line ?? "") . " @ BE: " . ($myerror['backend'] ?? $bee ?? "");
    $type = get_class($e);
    $err = [];
    $env = env("environment") == null ? "dev" : env("environment");
    if (strtolower($env) == "uat" || strtolower($env) == "staging") {
        include "_backend/app/library/PHPErrorClass.php";
        $clearMSG = PHPErrorClass::error_message($e);
        $err = [
            "code" => env($errorcode),
            "status" => "error",
            "message" => $clearMSG,
            "errorcode" => $hascode,
            "msg" => $message . " #" . $hascode,
            "trace" => $trace,
            "data" => []
        ];
    } else if (strtolower($env) == "prod" || strtolower($env) == "production") {
        include "_backend/app/library/PHPErrorClass.php";
        $clearMSG = PHPErrorClass::error_message($e, true);
        $err = [
            "code" => env($errorcode),
            "status" => "error",
            "message" => $clearMSG ?? "Server Error #" . $hascode,
            "msg" => $message . " #" . $hascode,
            "errorcode" => $hascode,
            "data" => []
        ];
    } else {
        $err = [
            "code" => env($errorcode),
            "status" => "error",
            "line" => $line,
            "file" => $file,
            "type" => $type,
            "trace" => $trace,
            "myerror" => $myerror,
            "errorcode" => $hascode,
            "error_message" => $getMessage,
            "msg" => $message,
            "message" => $cmsg,
            "data" => [],
            "request" => [
                "post" => postdata(),
                "get" => $_GET,
                "route" => $myerror['backend'] ?? $bee,
                "headers" => server_headers()
            ]
        ];
    }
    add_sql_log($message . " :: Trace= " . $getMessage, "be_errors", $hascode . " @" . $bee);
    return $err;
}