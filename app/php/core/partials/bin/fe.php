<?php

if (getenv("rootpath") == "" || getenv("rootpath") == null) {
    $rootpath = get_basixs_root_path();
    putenv("rootpath=$rootpath");
    $_ENV['rootpath'] = $rootpath;
}
define('rootpath', getenv('rootpath'));
define('pages', 'views/pages');
define('_backend', '_backend');
define('assets', 'views/assets');
define('codepath', 'views/code');

define('SUCCESS', getenv('success_code'));

define("success_code", getenv("success_code"));
define("error_code", getenv("error_code"));
define("db_error_code", getenv("db_error_code"));
define("notfound_code", getenv("notfound_code"));
define("forbidden_code", getenv("forbidden_code"));
define("unauthorized_code", getenv("unauthorized_code"));
define("badrequest_code", getenv("badrequest_code"));
define("warning_code", getenv("warning_code"));
define("no_internet_code", getenv("no_internet_code"));
define("backend_error_code", getenv("backend_error_code"));
define("failed_code", getenv("failed_code"));
define('app_name', getenv('app_name'));


define("now", date("Y-m-d H:i:s"));

if (getenv("time_zone")) {
    date_default_timezone_set(getenv("time_zone"));
}

if (! function_exists("now")) {
    function now(string|null $dateformat = "Y-m-d H:i:s", $timezone = null)
    {
        $dateformat ??= "Y-m-d H:i:s";
        if ($timezone) {
            $from = date_default_timezone_get();
            if ($from == $timezone) {
                return date($dateformat);
            }
            $dt = new DateTime("now", new DateTimeZone($from));
            $dt->setTimezone(new DateTimeZone($timezone));
            return $dt->format($dateformat);
        }
        return date($dateformat);
    }
}

if (! function_exists("env")) {
    function env(string $key)
    {
        return getenv($key);
    }
}

if (! function_exists("env_in_prod")) {
    function env_in_prod(): bool
    {
        if (env('environment') == "prod" || env("environment") == "production") {
            return true;
        }
        return false;
    }
}

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $v) {
            echo '<pre>';
            var_dump($v);
            echo '</pre>';
        }
        exit(1);
    }
}

if (! function_exists("val")) {
    function val(&$val, $datatype = "string")
    {
        $datatype = strtolower($datatype);
        if (! isset($val) || ! $val || $val == null || $val == "") {
            switch ($datatype) {
                case "string":
                case "text":
                    return "";
                    break;
                case "number":
                case "int":
                case "integer":
                case "float":
                case "double":
                case "num":
                    return "0";
                    break;
                case "array":
                case "object":
                    return [];
                    break;
                case "boolean":
                case "bool":
                    return false;
                    break;
                default:
                    return "";
                    break;
            }
        }
    }
}

if (! function_exists("decrypt_csrf_codetazer")) {
    function encrypted_csrf_codetazer($characters = 18, $strict = true)
    {
        $arr = range("A", "Z");
        for ($x = 1; $x <= 9; $x++) {
            $arr[] = (string) $x;
        }
        shuffle($arr);
        $str = "";
        if ($strict) {
            $s = date("ymdhis");
            for ($i = 0; $i <= $characters - 12; $i++) {
                $str .= (string)$arr[$i];
            }
            $str .= $s;
        } else {
            for ($i = 0; $i <= $characters; $i++) {
                $str .= (string)$arr[$i];
            }
        }
        return $str;
    }
}

if (! function_exists("csrf")) {
    function csrf()
    {
        $tkn = $_SESSION[ctr_secure_key] ?? null;
        return "<input type='hidden' name='csrf_ctr_field' value='$tkn'>";
    }
}

if (! function_exists("csrf_token")) {
    function csrf_token()
    {
        return $_SESSION[ctr_secure_key] ?? null;
    }
}

if (! function_exists("change_date")) {
    function change_date(string $date, string|null $interval)
    {
        $given = $date;
        $date = new DateTime($given);
        $date->modify($interval);
        ///or: $new   = date('Y-m-d H:i:s', strtotime($given . ' +20 minutes'));
        return $date->format('Y-m-d H:i:s');
    }
}

if (! function_exists("get_date")) {
    function get_date(string $date, string $format = "Y-m-d H:i:s")
    {
        $given = $date;
        $date  = new DateTime($given);
        return $date->format($format);
    }
}

if (! function_exists('page')) {
    function page(string|null $path = "/", array $param = [])
    {
        if (! $path || $path == "/") {
            if (empty($param)) return "/";
        } else {
            $path = trim($path, "/");
        }

        if (empty($param)) {
            return rootpath . "/" . $path;
        }

        $arr = [];
        foreach ($param as $k => $v) {
            $arr[] = $k . "=" . $v;
        }
        $parameter = implode("&", $arr);

        if (! $path || $path == "/") {
            return rootpath . "/?" . $parameter;
        }

        return rootpath . "/" . $path . "?" . $parameter;
    }
}

if (! function_exists('function_page')) {
    function function_page(string $path = "?", mixed $param = [])
    {
        if ($path === "?") {
            return rootpath . "/?funcpage=";
        }
        $bb = explode("?", $path);
        $path = $bb[0];
        $params = isset($bb[1]) ? "?" . $bb[1] : "";
        if ($param) {
            if (is_array($param)) {
                $getter = "";
                foreach ($param as $k => $v) {
                    $getter .= $k . "=" . $v . "&";
                }
                $params = "?" . rtrim($getter, "&");
            } else {
                $params = "?param=" . $param;
            }
        }
        if ($path == "" || $path == null) {
            return rootpath . $params;
        } else {
            $path = substr($path, -4) == ".php" ? $path : $path . ".php";
            return rootpath . "/?funcpage=" . $path . $params;
        }
    }
}

if (! function_exists('back_end')) {
    function back_end(string $path = "=")
    {
        if ($path === "=") {
            return rootpath . "/?be=";
        }
        if ($path === "?") {
            return rootpath . "/?be";
        }
        $bb = explode("?", $path);
        $path = $bb[0];
        $param = isset($bb[1]) ? "?" . $bb[1] : "";
        if ($path == "" || $path == null) {
            return rootpath . $param;
        } else {
            $path = substr($path, -4) == ".php" ? $path : $path . ".php";
            return rootpath . "/?be=" . $path . $param;
        }
    }
}

if (! function_exists("current_page")) {
    function current_page(bool $php_exention = false): string
    {
        $filename =  $_SESSION['basixs_current_page'] ?? getenv('app_name') ?? "Page not set";
        if (! $php_exention) {
            $filename = substr($filename, -4) === '.php' ? substr($filename, 0, -4) : $filename;
            return $filename;
        }

        return $filename;
    }
}

if (! function_exists("page_title")) {
    function page_title()
    {
        return $_SESSION['basixs_current_page_title'];
    }
}

if (! function_exists("set_page_title")) {
    function set_page_title(string $pagetitle)
    {
        $_SESSION['basixs_current_page_title'] = $pagetitle;
    }
}

if (! function_exists('_backend')) {
    function _backend(string $path = "")
    {
        if ($path == "" || $path == null) {
            return _backend;
        } else {
            return _backend . "/" . $path;
        }
    }
}
if (! function_exists('assets')) {
    function assets(string $path = "")
    {
        if ($path == "" || $path == null) {
            return assets;
        } else {
            return assets . "/" . $path;
        }
    }
}

if (! function_exists('codepath')) {
    function codepath(string $path = "")
    {
        if ($path == "" || $path == null) {
            return codepath;
        } else {
            return codepath . "/" . $path;
        }
    }
}

if (! function_exists("ctr_generate_request_id")) {
    function ctr_generate_request_id()
    {
        $date = date("ymdhis");
        $arr = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
        shuffle($arr);
        $date = date("ymdHis");
        $req = $arr[0] . $arr[1] . $arr[8] . $date . $arr[3] . $arr[8] . $arr[9];
        return $req;
    }
}

if (! function_exists("ctr_get_current_request_id")) {
    function ctr_get_current_request_id()
    {
        return $_SESSION["ctr_unique_request_id_x0015"] ?? null;
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

if (! function_exists("get")) {
    function get(string $key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : null;
    }
}


if (! function_exists('href')) {
    function href(string $path = "")
    {
        if ($path == "" || $path == null) {
            header('location: ' . rootpath);
        } else {
            header('location: ' . rootpath . "/?page=$path");
        }
    }
}

if (! function_exists('redirect')) {
    function redirect(string $path = "", string $type = "page", int $time = 0)
    {
        if ($type == "page") {
            header("refresh: $time; url=" . rootpath . "/?page=$path");
        }
        if ($type == "func") {
            header("refresh: $time; url=" . rootpath . "/?funcpage=$path");
        }
    }
}

if (! function_exists("write_sql_log")) {
    function write_sql_log($message)
    {
        $setting = getenv('sql_logs');
        if ($setting) {
            $filename = "sql_" . date("Y-M-d") . "_yros.log";
            $logfile =  "_backend/app/dblogs/" . $filename;
            $formatted_message = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
            file_put_contents($logfile, $formatted_message, FILE_APPEND);
        }
    }
}

if (! function_exists("write_sql_error")) {
    function write_sql_error($message, string $query = "")
    {;
        $setting = getenv('sql_errors');
        if ($setting == true) {
            $logfile = "app/db_errors/sqlerrors.txt";

            $message = preg_replace('/\s+/', ' ', trim($message));
            $query = preg_replace('/\s+/', ' ', trim($query));

            $formatted_message = "[" . date('Y-m-d H:i:s') . "] " . $message . " ==>> QUERY: " . $query . PHP_EOL . PHP_EOL;
            file_put_contents($logfile, $formatted_message, FILE_APPEND);
        }
    }
}

if (! function_exists("view_page")) {
    function view_page(string $page, array $variables = [])
    {
        $page = substr($page, -4) == ".php" ? $page : $page . ".php";
        if (file_exists("_frontend/pages/$page")) {
            if (!empty($variables)) {
                extract($variables);
            }
            include "_frontend/pages/$page";
        } else {
            echo "<b style='color:red;background:black;padding:5px;font-weight:bold;'>Page $page doesn't exist.! Please check _frontend/pages/$page</b>";
        }
    }
}

if (! function_exists("include_page")) {
    function include_page(string $page, array $variables = [])
    {
        $page = substr($page, -4) == ".php" ? $page : $page . ".php";
        if (file_exists("views/includes/$page")) {
            if (!empty($variables)) {
                extract($variables);
            }
            include "views/includes/$page";
        } else {
            throw new Exception("Include page $page doesn't exist.! Please check views/pages/$page");
        }
    }
}

if (! function_exists("display")) {
    function display($text)
    {
        if (is_array($text)) {
            print_r($text);
        } else {
            echo $text;
        }
    }
}

if (! function_exists("display_error111")) {
    function display_error111(string $message)
    {
        $str = new Exception($message);
        $arr = explode("#", $str);
        $err = [];
        foreach ($arr as $r) {
            if (strpos($r, '\app\system\helpers') !== false) {
            } elseif (strpos($r, '\app\system') !== false) {
            } elseif (strpos($r, '\index.php(11): require_once(') !== false) {
            } else {
                $err[] = $r;
            }
        }
        $ff = implode("\n", $err);
        $final = $message . " " . $ff;
        return $final;
    }
}

if (! function_exists("array_is_multidimensional")) {
    function array_is_multidimensional(array $arr)
    {
        foreach ($arr as $element) {
            if (is_array($element)) {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists("php_file")) {
    function php_file($pagename)
    {
        $mainpage = substr($pagename, -4) == ".php" ? $pagename : $pagename . ".php";
        return $mainpage;
    }
}

if (! function_exists("ctrx_all_routes")) {
    function ctrx_all_routes($phpfile = false)
    {
        $baseDir = "";
        $ep = ctr_endpoint();
        if ($ep == "FE") {
            $baseDir = 'views/pages';
        } else {
            $baseDir = '_controller';
        }

        $arrs = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $item->getPathname());

            $relativePath = str_replace(DIRECTORY_SEPARATOR, "/", $relativePath);
            if ($item->isDir()) {
                continue;
            } else {
                if (str_starts_with($relativePath, "api/")) continue;
                if ($phpfile) {
                    $arrs[] = $relativePath;
                } else {
                    $arrs[] = basixs_php_rem($relativePath);
                }
            }
        }
        return $arrs;
    }
}

if (! function_exists("ctrx_endpoint")) {
    function ctrx_endpoint()
    {
        $param = ctrx_param;
        if (str_starts_with($param, "api/")) {
            return "BE";
        }
        return "FE";
    }
}

if (! function_exists("use_middleware")) {
    function use_middleware(string $middleware)
    {
        $model = substr($middleware, -4) == ".php" ? $middleware : $middleware . ".php";
        $ep = ctrx_endpoint();
        $gfile = "";
        if ($ep == "FE") $gfile = "views/app/middleware/";
        else $gfile = "app/middleware/";
        if (! file_exists($gfile . $model)) {
            throw new Exception("Middleware '$middleware' not exist.!");
        }
        include $gfile . $model;
    }
}

if (! function_exists("ctrx_get_routes")) {
    function ctrx_get_routes($parent, $phpfile = false)
    {
        $ep = ctrx_endpoint();
        $baseDir = "";
        if ($ep == "FE") {
            $baseDir = "views/pages/$parent";
        } else {
            $baseDir = "_controller/$parent";
        }

        $arrs = [];
        if (! is_dir($baseDir)) {
            throw new Exception("ctr_get_routes error: $baseDir not exist");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $item->getPathname());

            $relativePath = str_replace(DIRECTORY_SEPARATOR, "/", $relativePath);
            if ($item->isDir()) {
                continue;
            } else {
                if ($phpfile) {
                    $arrs[] = $relativePath;
                } else {
                    $arrs[] = $parent . "/" . basixs_php_rem($relativePath);
                }
            }
        }
        return $arrs;
    }
}

if (! function_exists("ctrx_get_files")) {
    function ctrx_get_files($parent = "", $basePath = "", $phpfile = false)
    {
        $baseDir = "";
        if (! $basePath) {
            $baseDir = $parent;
        } else {
            if ($parent) {
                $baseDir = $basePath . "/" . $parent;
            } else {
                $baseDir = $basePath;
            }
        }
        $arrs = [];

        if (! is_dir($baseDir)) {
            throw new Exception("ctr_get_files error: $baseDir not exist");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $item->getPathname());

            $relativePath = str_replace(DIRECTORY_SEPARATOR, "/", $relativePath);
            if ($item->isDir()) {
                continue;
            } else {
                if ($phpfile) {
                    $arrs[] = $relativePath;
                } else {
                    $newPath = substr($relativePath, -4) === '.php' ? substr($relativePath, 0, -4) : $relativePath;
                    if ($parent) {
                        $arrs[] = $parent . "/" . $newPath;
                    } else {
                        $arrs[] = $newPath;
                    }
                }
            }
        }
        return $arrs;
    }
}

if (! function_exists("get_json")) {
    function get_json(string $jsonfile, string $path = null)
    {
        if (! $path) {
            $ep = ctr_endpoint();
            if ($ep == "FE") {
                $path = "_frontend/app/auto/json/";
            } else {
                $path = "_backend/application/json/";
            }
        }
        $jsonfile = str_ends_with($jsonfile, ".json") ? $jsonfile : $jsonfile . ".json";
        $json = file_get_contents($path . $jsonfile);
        if (! $json) {
            throw new Exception("Error on reading json file");
        }
        $data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            throw new Exception(json_last_error_msg());
        }
        return null;
    }
}

if (! function_exists("autoload_routing")) {
    function autoload_routing(string|array $filename)
    {
        if (!$filename) {
            return false;
        }
        $ep = ctr_endpoint();
        $fl = "";
        if ($ep == "FE") {
            $fl = "_frontend/app/auto/routing/";
        } else {
            $fl = "_backend/application/routing/";
        }
        if (is_array($filename)) {
            foreach ($filename as $f) {
                $loadpage = substr($f, -4) == ".php" ? $f : $f . ".php";
                if ($ep == "FE") {
                    include $fl . $loadpage;
                } else {
                    include $fl . $loadpage;
                }
            }
        } else {
            $loadpage = substr($filename, -4) == ".php" ? $filename : $filename . ".php";
            include $fl . $loadpage;
        }
    }
}

define('page', page(""));
