<?php

/**
 * This is CTRX framework
 * Made by Tyrone Limen Malocon
 */
require_once 'vendor/autoload.php';
include "app/php/core/partials/envloader.php";

session_start();

date_default_timezone_set(env('time_zone'));

$basixserver = $_SERVER['HTTP_HOST'];
$req = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$req = trim($req, "/");
$rooth = getenv("rootpath") ?? "http://localhost:9999";
$rooth = str_ends_with($rooth, "/") ? substr($rooth, 0, -1) : $rooth;
$b_all = $basixserver . "/" . $req;
define("ctrx_param", strtolower($req));

$files = glob('app/helper/*.php');
$system = glob('app/php/core/partials/bin/*.php');
include "app/php/core/partials/be.php";

foreach ($files as $k => $v) {
    include $v;
}

foreach ($system as $k => $v) {
    include $v;
}

include "app/php/core/partials/ctrxc.php";

//$req = $req == null || $req == "" ? rem_php(fe_config("main_page")) : $req;

define("roothpath", getenv("roothpath"));

if (str_starts_with($req, "api/")) {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    });
    try {
        $_SESSION['ctrx_endpoint'] = "BE";
        $serve = "";
        $beconfig = glob('app/config/*.php');

        foreach ($beconfig as $k => $v) {
            include $v;
        }

        $_SESSION['ctr_unique_request_id_x0015'] = ctr_generate_request_id();

        $req = strtolower($req);
        if (str_starts_with($req, "api/")) $serve = "api";
        $newReq = "";
        if ($serve == "api") $newReq = str_replace("api/", "", $req);
        $reqmeth = strtolower(request_method());

        $_SESSION['basixs_current_be'] = $newReq;
        defined("route") || define("ROUTE", rem_php($newReq));
        if (getenv("cross_origin_sharing") == "yes") {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: " . getenv("allowed_headers"));
        } else {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $rpath = rootpath;
            if ($origin !== '' && $origin !== $rpath) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode([
                    "code" => 403,
                    "message" => "Sorry, we are unable to share resources to '$org'"
                ]);
                exit;
            }
        }
        $is_in = $_REQUEST["ctrx_" . $reqmeth . "_" . $newReq] ?? null;
        if (! $is_in) {
            ctrx_response(["code" => env('notfound_code'), "message" => "Route '$newReq' not found"], 500);
        }
        $route = append_php($is_in['route']);
        if (isset($is_in['middleware'])) {
            foreach ($is_in['middleware'] as $k => $v) {
                $mw = append_php($v);
                include "app/middleware/$mw";
            }
            if (! file_exists("app/_controller/$route")) {
                ctrx_response(["code" => env('notfound_code'), "message" => "Controller '$route' not found.!", "req" => $_REQUEST], 500);
            }
        }
        include "app/_controller/$route";
    } catch (Throwable $e) {
        ctrx_response(["code" => error_code, "message" => $e->getMessage(), "trace" => $e->getTrace()], 500);
    } catch (PDOException $e) {
        ctrx_response(["code" => error_code, "message" => $e->getMessage(), "trace" => $e->getTrace()], 500);
    } catch (Exception $e) {
        ctrx_response(["code" => error_code, "message" => $e->getMessage(), "trace" => $e->getTrace()], 500);
    } catch (InvalidArgumentException $e) {
        ctrx_response(["code" => error_code, "message" => $e->getMessage(), "trace" => $e->getTrace()], 500);
    } finally {
        restore_error_handler();
    }
    exit;
} else {
    $_SESSION['ctrx_endpoint'] = "FE";

    error_reporting(E_ALL);
    if (env_in_prod()) {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
    } else {
        ini_set('display_errors', '1');
    }


    ob_start();
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });

    try {
        $feconfig = glob('views/app/config/*.php');
        foreach ($feconfig as $k => $v) {
            $vv = append_php($v);
            include $vv;
        }

        include "app/php/core/partials/cx.php";

        $view_config = file_get_contents("views/config.json");
        $view_config = json_decode($view_config, true);
        $mainpage = $view_config['main_page'] ?? "main";
        $mainpage = append_php($mainpage);
        $mainnophp = rem_php($mainpage);

        $is_in = $_REQUEST["ctrxfe_" . $mainnophp] ?? null;
        if ($is_in) {
            $mw = $is_in["middleware"] ?? null;
            $parent = $is_in["parent"] ?? null;
        }

        if (!$req || $req == "") {
            $page_to_include = "views/pages/" . $mainpage;
            if (!file_exists($page_to_include)) {
                $errorpage = $view_config["page_not_found"] ?? "404";
                $errorpage = append_php($errorpage);
                include "views/core/errors/" . $errorpage;
                exit;
            }
            include $page_to_include;
            exit;
        }

        $page = append_php($req);
        $fullpath = "views/pages/" . $page;

        if (!file_exists($fullpath)) {
            $errorpage = $view_config["page_not_found"] ?? "404";
            $errorpage = append_php($errorpage);
            include "views/core/errors/" . $errorpage;
            exit;
        }

        include $fullpath;
    } catch (Throwable $e) {
        ob_clean();
        if (env_in_prod()) {

            $servererror = $view_config["prod_error_page"] ?? "dev_error";
            $servererror = append_php($servererror);
            $file =  "views/core/errors/$servererror";
            include $file;
            exit;
        } else {
            $error = [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => explode("\n", $e->getTraceAsString()),
            ];

            $servererror = $view_config["dev_error_page"] ?? "dev_error";
            $servererror = append_php($servererror);
            include "views/core/errors/$servererror";
        }
    } finally {
        restore_error_handler();
        ob_end_flush();
    }
}
