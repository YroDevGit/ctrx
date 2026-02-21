<?php
if (! function_exists('json_response')) {
    function json_response(array $data, int $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}

if (! function_exists('success_response')) {
    function success_response(array $data, int $status = 200)
    {
        $data['be_response'] = "success";
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}

if (! function_exists('error_response')) {
    function error_response(array $data, int $status = 200)
    {
        $data['be_response'] = "error";
        header('Content-Type: application/json');
        http_response_code(getenv("error_code"));
        echo json_encode($data);
        exit;
    }
}

if (! function_exists("json_reponse_data")) {
    function json_reponse_data(int $code, string $status, string $message, array $data)
    {
        $result = ["code" => $code, "status" => $status, "message" => $message, "data" => $data];
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}

if (! function_exists('json_error')) {
    function json_error(array $details = [], string|null $message = null, int $status = 200)
    {
        json_response([
            "code" => getenv("error_code"),
            "status" => "ERROR",
            "message" => $message ?? "ERROR",
            "details" => $details
        ], $status);
        exit;
    }
}

if (! function_exists('json_success')) {
    function json_success(array $details = [], string|null $message = null, int $status = 200)
    {
        json_response([
            "code" => getenv("success_code"),
            "status" => "SUCCESS",
            "message" => $message ?? "SUCCESS",
            "details" => $details,
        ], $status);
        exit;
    }
}

if (! function_exists('json_notfound')) {
    function json_notfound(array $details = [], string|null $message = null, int $status = 200)
    {
        json_response([
            "code" => getenv("notfound_code"),
            "status" => "NOT_FOUND",
            "message" => $message ?? "404 not found",
            "details" => $details,
        ], $status);
        exit;
    }
}

if (! function_exists('json_failed')) {
    function json_failed(array $details = [], string|null $message = null, int $status = 200)
    {
        json_response([
            "code" => getenv("failed_code"),
            "status" => "FAILED",
            "message" => $message ?? "Request failed",
            "details" => $details,
        ], $status);
        exit;
    }
}

if (! function_exists('json_badrequest')) {
    function json_badrequest(array $details = [], string|null $message = null, int $status = 200)
    {
        json_response([
            "code" => getenv("badrequest_code"),
            "status" => "BAD_REQUEST",
            "message" => $message ?? "Bad Request",
            "details" => $details,
        ], $status);
        exit;
    }
}

if (! function_exists('json_forbidden')) {
    function json_forbidden(array $details = [], string|null $message = null, int $status = 200)
    {
        json_response([
            "code" => getenv("forbidden_code"),
            "status" => "ACCESS_FORBIDDEN",
            "message" => $message ?? "Request Forbidden",
            "details" => $details,
        ], $status);
        exit;
    }
}

if (! function_exists('json_unauthorized')) {
    function json_unauthorized(array $details = [], string|null $message = null, int $status = 200)
    {
        json_response([
            "code" => getenv("unauthorized_code"),
            "status" => "UNAUTHORIZED",
            "message" => $message ?? "Unauthorized Request",
            "details" => $details,
        ], $status);
        exit;
    }
}

if (! function_exists("post")) {
    /** (Any) returns the value of the post */
    function post(string $inputname = null, bool|null|string $trim = true)
    {
        $data = $_POST ?? [];
        if (! $inputname) {
            return $data;
        }

        $ret = isset($data[$inputname]) ? $data[$inputname] : null;

        if ($ret && $trim) {
            if (is_string($trim)) {
                $ret = trim($ret, $trim);
            } else {
                $ret = trim($ret);
            }
        }

        return $ret;
    }
}

if (! function_exists("postdata")) {
    /** (Any) returns the value of the post */
    function postdata()
    {
        $post = [];
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $post = $data;
            } else {
                $post = [];
            }
        } else {
            $post = $_POST;
        }
        return $post;
    }
}

if (! function_exists("input")) {
    /** (Any) returns the value of the get */
    function input(string $inputname)
    {
        return post($inputname);
    }
}

if (! function_exists("get")) {
    /** (Any) returns the value of the get */
    function get(string $inputname)
    {
        return isset($_GET[$inputname]) ? $_GET[$inputname] : null;
    }
}
if (! function_exists("getall")) {
    /** (Any) returns the value of the get */
    function getall()
    {
        return $_GET;
    }
}
if (! function_exists("postall")) {
    /** (Any) returns the value of the get */
    function postall()
    {
        return $_POST;
    }
}
if (! function_exists("getallfiles")) {
    /** (Any) returns the value of the get */
    function getallfiles()
    {
        return $_FILES;
    }
}
if (! function_exists("postfile")) {
    /** (Any) returns the value of the get */
    function postfile(string $inputname)
    {
        return isset($_FILES[$inputname]) ? $_FILES[$inputname] : null;
    }
}

if (! function_exists("has_internet_connection")) {
    function has_internet_connection($url = "http://www.google.com")
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return true;
        } else {
            curl_close($ch);
            return false;
        }
    }
}

if (! function_exists("add_sql_log")) {
    function add_sql_log($string, string $type = "info", string $intro = "")
    {
        $arr = range('A', 'Z');
        $arr = array_merge($arr, range(1, 9));
        shuffle($arr);

        if (isset($_SESSION['set_sql_batch'])) {
            $mx = $_SESSION['set_sql_batch'];
        } else {
            $mx = $arr[0] . $arr[1] . $arr[2] . $arr[3] . $arr[4];
            $_SESSION['set_sql_batch'] = $mx;
        }

        $logConfig = [
            "info"     => ["env" => "sql_logs",   "dir" => "logs/sql_logs",   "prefix" => "INFO"],
            "error"    => ["env" => "sql_errors", "dir" => "logs/sql_errors", "prefix" => "ERROR"],
            "query"    => ["env" => "query_logs", "dir" => "logs/query_logs", "prefix" => $intro],
            "be_errors" => ["env" => "be_errors",  "dir" => "logs/be_errors",  "prefix" => $intro],
        ];

        if (!isset($logConfig[$type])) {
            return false;
        }

        $env = $logConfig[$type]["env"];
        $dir = $logConfig[$type]["dir"];
        $prefix = $logConfig[$type]["prefix"];

        if (getenv($env) !== "true") {
            return false;
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $logfile = $dir . "/" . date("Y-m-d") . ".log";
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "$prefix: ($mx) [$timestamp] $string\n";

        file_put_contents($logfile, $logEntry, FILE_APPEND | LOCK_EX);
        return true;
    }
}

if (! function_exists("my_log")) {
    function my_log($text, $parent = "mylogs", string $intro = "")
    {
        $dir = "logs/$parent";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $logfile = $dir . "/" . date("Y-m-d") . ".log";
        $timestamp = date('Y-m-d H:i:s');
        $intro = $intro === "" ? "" : $intro . " ";
        $logEntry = $intro . "[$timestamp] $text\n";

        file_put_contents($logfile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

if (! function_exists("hash_password")) {
    function hash_password(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

if (! function_exists("verify_password")) {
    function verify_password(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
if (! function_exists("generate_token")) {
    function generate_token(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
if (! function_exists("generate_random_string")) {
    function generate_random_string(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
if (! function_exists("generate_random_number")) {
    function generate_random_number(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
if (! function_exists("generate_random_string")) {
    function generate_random_string(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}

if (! function_exists("use_helper")) {
    function use_helper(string $helper)
    {
        $ep = ctrx_endpoint();
        $hfolder = "app/helper/";
        if ($ep == "FE") $hfolder = "views/app/helper/";
        $modelFile = substr($helper, -4) === ".php" ? $helper : $helper . ".php";
        include $hfolder . $modelFile;
    }
}


if (! function_exists("use_base")) {
    function use_base(string $base)
    {
        $model = $base;
        $modelFile = substr($model, -4) == ".php" ? $model : $model . ".php";
        include "_backend/app/base/" . $modelFile;

        $className = basename($model, ".php");
        return new $className();
    }
}

function interpolate_query(string $query, array $params, $type = "undefined"): string
{
    $escapedParams = [];

    foreach ($params as $key => $param) {
        if (is_null($param)) {
            $escapedParams[$key] = 'NULL';
        } elseif (is_bool($param)) {
            $escapedParams[$key] = $param ? '1' : '0';
        } elseif (is_numeric($param)) {
            $escapedParams[$key] = $param;
        } else {
            $escapedParams[$key] = "'" . addslashes($param) . "'";
        }
    }
    foreach ($escapedParams as $key => $value) {
        $placeholder = strpos($key, ':') === 0 ? $key : ':' . $key;
        $query = preg_replace('/' . preg_quote($placeholder, '/') . '\b/', $value, $query);
    }
    add_sql_log($query, "query", $type);
    return $query;
}


if (! function_exists("set_sql_batch")) {
    function set_sql_batch(string $batch = "")
    {
        if ($batch == "" || $batch == null) {
            unset($_SESSION['set_sql_batch']);
        } else {
            $_SESSION['set_sql_batch'] = $batch;
        }
    }
}

if (! function_exists("current_be")) {
    function current_be(bool $php_exention = false): string
    {
        $filename =  $_SESSION['basixs_current_be_ctrx'] ?? "Page not set";
        if (! $php_exention) {
            $filename = substr($filename, -4) === '.php' ? substr($filename, 0, -4) : $filename;
            return $filename;
        }

        return $filename;
    }
}

if (! function_exists("wildcard")) {
    function wildcard(string|null $value)
    {
        $val = $value ?? "";
        return "%" . $val . "%";
    }
}

if (! function_exists("paginate_offset")) {
    function paginate_offset($page = 1, $limit = 10)
    {
        return max(0, ((int)$page - 1) * $limit);
    }
}

if (! function_exists("db_paginate")) {
    function db_paginate($page = 1, $limit = 10)
    {
        return ["limit" => $limit, "offset" => paginate_offset($page, $limit)];
    }
}

if (! function_exists("server_headers")) {
    function server_headers(string|null $searchKey = null)
    {
        $headers = [];
        foreach ($_SERVER as $serverKey => $value) {
            if (strpos($serverKey, 'HTTP_') === 0) {
                $exp = str_replace("HTTP_", "", $serverKey);
                $headers[strtolower($exp)] = $value;
                $headers[strtoupper($exp)] = $value;
            }
        }
        if ($searchKey === null) {
            return $headers;
        } else {
            return $headers[$searchKey] ?? null;
        }
    }
}

if (! function_exists("request_method")) {
    function request_method()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        return $method;
    }
}

if (! function_exists("validate_request_method")) {
    function validate_request_method(String $req_method)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        return strtolower($method) == strtolower($req_method);
    }
}

if (! function_exists("set_request_method")) {
    function set_request_method(String $req_method)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if (strtoupper($req_method) != strtoupper($method)) {
            $be = current_be();
            $errmsg = "Request method should be " . strtoupper($req_method) . "\n@ " . $be;
            if (strtolower($env) == "prod" || strtolower($env) == "production" || strtolower($env) == "uat" || strtolower($env) == "staging") {
                $errmsg = "Request method should be " . strtoupper($req_method);
            }
            json_response(["code" => getenv("badrequest_code"), "message" => $errmsg, "status" => "request_method_invalid"], 501);
        }
    }
}

if (! function_exists("my_hash")) {
    function my_hash(String $text, $length = 16)
    {
        return substr(md5($text), 0, $length);
    }
}

if (! function_exists("storage")) {
    function storage(string|null $file = null)
    {
        $path = "views/core/partials/storage";
        if (! $file) return $path;

        return $path . "/" . $file;
    }
}

if (! function_exists("in_table")) {
    function in_table(string $tableColumn, string|null $value, bool $wildCard = false)
    {
        $explode = explode(":", $tableColumn);
        $table = $explode[0] ?? null;
        $columns = $explode[1] ?? null;

        if (! $table || ! $columns) {
            throw new Exception("Invalid in_table format");
        }

        if ($wildCard) $value = "%" . $value . "%";

        if (str_contains($columns, ",")) {
            $exp = explode(",", $columns);
            $isIn = false;
            foreach ($exp as $kk => $vv) {
                $data = [];
                if ($wildCard) {
                    $data = \Classes\DB::find($table, ["like" => [$vv => $value]]);
                } else {
                    $data = \Classes\DB::find($table, [$vv => $value]);
                }
                if ($data) {
                    $isIn = true;
                    break;
                }
            }
            return $isIn;
        } else {
            $data = [];
            if ($wildCard) {
                $data = \Classes\DB::find($table, ["like" => [$columns => $value]]);
            } else {
                $data = \Classes\DB::find($table, [$columns => $value]);
            }
            if ($data) return true;
            return false;
        }
    }
}

if (! function_exists("in_table_strict")) {
    function in_table_strict(string $tableColumn, string|null $value)
    {
        $wildCard = false;
        $explode = explode(":", $tableColumn);
        $table = $explode[0] ?? null;
        $columns = $explode[1] ?? null;

        if (! $table || ! $columns) {
            throw new Exception("Invalid in_table format");
        }

        $oldValue = $value;
        if ($wildCard) $value = "%" . $value . "%";

        if (str_contains($columns, ",")) {
            $exp = explode(",", $columns);
            $isIn = false;
            foreach ($exp as $kk => $vv) {
                $data = [];
                if ($wildCard) {
                    $data = \Classes\DB::find($table, ["like" => [$vv => $value]]);
                } else {
                    $data = \Classes\DB::find($table, [$vv => $value]);
                }
                $data = \Classes\Collection::data($data)->extract($columns)->exec();
                if (in_array($oldValue, $data)) {
                    $isIn = true;
                    break;
                }
            }
            return $isIn;
        } else {
            $data = [];
            if ($wildCard) {
                $data = \Classes\DB::find($table, ["like" => [$columns => $value]]);
            } else {
                $data = \Classes\DB::find($table, [$columns => $value]);
            }
            $data = \Classes\Collection::data($data)->extract($columns)->exec();
            if (in_array($oldValue, $data)) return true;
            return false;
        }
    }
}

if(! function_exists("backend_only")){
    function backend_only(callable|null $func = null){
        $ep = ctrx_endpoint();
        if($ep !== "BE"){
            if(is_null($func)){
                die("Client access declined by server.");
            }else{
                $func();
            }
        }
    }
}


if(! function_exists("what_ctrx_file")){
    function what_ctrx_file(){
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        return $trace[1]['file'];;
    }
}

if(! function_exists("allow_client_access")){
    function allow_client_access(){
        $backendPath = "app/php/core/partials/backend.php";
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $trace[0]['file'];
        if(! str_contains($file, "app\\model\\") && ! str_contains($file, "app\\library\\") && ! str_contains($file, "app\\base\\")){
            throw new Exception("Unable to call this function on client side");
        }
        $allFiles = get_included_files();
        $hasIt = false;
        foreach($allFiles as $k=>$v){
            if(str_contains($v, $backendPath)){$hasIt = true; break;}
        }
        if(! $hasIt){
            include_once $backendPath;
        }
    }
}

if(! function_exists("is_already_included")){
    function is_already_included(string $filepath):bool{
        $allFiles = get_included_files();
        $hasIt = false;
        foreach($allFiles as $k=>$v){
            if(str_contains($v, filepath)){$hasIt = true; break;}
        }
        return $hasIt;
    }
}