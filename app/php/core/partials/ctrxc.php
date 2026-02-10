<?php


if (! function_exists("rem_php")) {
    function rem_php(string|null $name)
    {
        if (! $name) return null;
        $ret = str_ends_with($name, ".php") ? substr($name, 0, -4) : $name;
        return $ret;
    }
}

if (! function_exists("append_php")) {
    function append_php(string $name)
    {
        $ret = str_ends_with($name, ".php") ? $name : $name . ".php";
        return $ret;
    }
}

if (! function_exists("load_routes")) {
    function load_routes(string|array ...$routes)
    {
        $serve = "";
        $ep = ctrx_endpoint();

        if ($ep == "FE") $serve = "views/app/routes/";
        else $serve = "app/_routes/";
        foreach ($routes as $k => $routing) {
            if (is_string($routing)) {
                $routing = str_ends_with($routing, ".php") ? $routing : $routing . ".php";
                unset($_REQUEST['ctrx_global_prefix']);
                include_once $serve . $routing;
            } else if (is_array($routing)) {
                foreach ($routing as $k => $r) {
                    if (! $k) {
                        unset($_REQUEST['ctrx_global_prefix']);
                        $r = append_php($r);
                        include_once $serve . $r;
                        continue;
                    }
                    $re = str_ends_with($k, ".php") ? $k : $k . ".php";
                    $_REQUEST['ctrx_global_prefix'] = $r;
                    include_once $serve . $re;
                }
            }
        }
    }
}

if (! function_exists('json_response')) {
    function json_response(array $data, int $status = 200)
    {
        header('Content-Type: application/json');
        $data["request_id"] = ctr_get_current_request_id();
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}

if (! function_exists('ctrx_response')) {
    function ctrx_response(array $data, int $status = 200, Throwable|PDOException|Exception|InvalidArgumentException $error = null)
    {
        $fulltrace = env("full_trace");
        header('Content-Type: application/json');
        http_response_code($status);
        $reqid = ctr_get_current_request_id();
        $data["request_id"] = $reqid;
        if ($status == 500) {
            if ($error) {
                $e_msg = $error->getMessage();
                $e_file = $error->getFile();
                $e_line = $error->getLine();
                $e_trace = $error->getTrace();

                $fandl = "@";

                if (! str_contains($e_file, "\app\php\core")) {
                    $fandl = "@" . $e_file . " Line " . $e_line . " ";
                }

                $all = [];
                foreach ($e_trace as $k => $v) {
                    $file = $v['file'] ?? null;
                    if (! $file) {
                        continue;
                    }

                    if ($fulltrace == "no" && str_contains($file, "\app\php\core")) {
                        continue;
                    }
                    $all[] = $v;
                }
                $e_error = json_encode($all);
                if (getenv("error_logs") == "yes") {
                    ctrx_log($e_msg . " " . $fandl . "Trace: " . $e_error, "app", $reqid);
                }
            }
            if (env_in_prod()) {
                $newd = json_encode($data);
                $req = $data["request_id"];
                $data['message'] = "SERVER ERROR $req";
                unset($data['trace']);
            }
        }
        echo json_encode($data);
        exit;
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

if (! function_exists("fe_config")) {
    function fe_config(string $key)
    {
        $view_config = file_get_contents("views/config.json");
        $view_config = json_decode($view_config, true);
        $mainpage = $view_config[$key] ?? null;
        return $mainpage;
    }
}

function ctrx_log(string $message, string $parent, string $id = null, string $filename = null)
{
    $folder = "logs/" . $parent;
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
    $filename = $filename ?? date("Y-m-d") . ".php";

    $filePath = $folder . '/' . $filename . '-log.php';
    $time = date('Y-m-d H:i:s');

    $id = $id ?? ctr_get_current_request_id();

    $protection = "<?php\nif(!defined('roothpath')) die('unauthorized access');\n\n";

    $logEntry = "\$log['$time'][$id] = " . var_export($message, true) . ";\n";

    if (!file_exists($filePath)) {
        $content = $protection . $logEntry;
        file_put_contents($filePath, $content, LOCK_EX);
    } else {
        file_put_contents($filePath, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

if (! function_exists("ctrx_same_origin")) {
    function ctrx_same_origin()
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'];
        $origin = $_SERVER['HTTP_ORIGIN'];

        $serverOrigin = $scheme . '://' . $host;

        if ($origin === $serverOrigin) {
            return true;
        } else {
            return false;
        }
    }
}
