<?php
//CodeYro
function encrypt($data, string $key = null)
{
    if ($data == null || $data == "") {
        return null;
    }
    $cipher = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted_data = null;
    if ($key == null || $key == "") {
        $encrypted_data = openssl_encrypt($data, $cipher, getenv("encrypt_key"), 0, $iv);
    } else {
        $encrypted_data = openssl_encrypt($data, $cipher, $key, 0, $iv);
    }

    $combined_data = $iv . $encrypted_data;

    $encrypted_data = base64_encode($combined_data);

    $encrypted_data = strtr($encrypted_data, [
        '+' => '-',
        '/' => '_',
        '=' => '',
        '&' => '%26',
        '#' => '%23',
    ]);
    return $encrypted_data;
}

function decrypt($encrypted_data, string $key = null)
{
    if ($encrypted_data == null || $encrypted_data == "") {
        return null;
    }
    $cipher = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($cipher);

    $encrypted_data = strtr($encrypted_data, [
        '-' => '+',
        '_' => '/',
        '%26' => '&',
        '%23' => '#',
    ]);

    $padding_needed = 4 - (strlen($encrypted_data) % 4);
    if ($padding_needed !== 4) {
        $encrypted_data .= str_repeat('=', $padding_needed);
    }

    $decoded_data = base64_decode($encrypted_data, true);
    if ($decoded_data === false) {
        return null;
    }

    if (strlen($decoded_data) < $iv_length) {
        return null;
    }
    $iv = substr($decoded_data, 0, $iv_length);
    $encrypted_data = substr($decoded_data, $iv_length);

    $decryption_key = $key ?: getenv("encrypt_key");
    $decrypted_data = openssl_decrypt($encrypted_data, $cipher, $decryption_key, 0, $iv);

    if ($decrypted_data === false || !mb_check_encoding($decrypted_data, 'UTF-8')) {
        return null;
    }

    return $decrypted_data;
}


function load_auto(string ...$auto){
    $ep = ctrx_endpoint();
    $path = "app/auto/";
    if($ep == "FE") $path = "views/app/auto/";
    foreach($auto as $k=>$v){
        $vv = append_php($v);
        include_once $path.$vv;
    }
}

function load_php(string ...$auto){
    foreach($auto as $k=>$v){
        $vv = append_php($v);
        include_once "views/app/php/".$vv;
    }
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
    $env = getenv("environment") == null ? "dev" : getenv("environment");
    if (strtolower($env) == "uat" || strtolower($env) == "staging") {
        include "_backend/app/library/PHPErrorClass.php";
        $clearMSG = PHPErrorClass::error_message($e);
        $err = [
            "code" => getenv($errorcode),
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
            "code" => getenv($errorcode),
            "status" => "error",
            "message" => $clearMSG ?? "Server Error #" . $hascode,
            "msg" => $message . " #" . $hascode,
            "errorcode" => $hascode,
            "data" => []
        ];
    } else {
        $err = [
            "code" => getenv($errorcode),
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
