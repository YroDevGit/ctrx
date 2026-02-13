<?php

/**
 * This is CTRX framework
 * Made by Tyrone Limen Malocon
 */

/**
 * Vendor Autoload (Composer support)
 */
require_once 'vendor/autoload.php';
include "app/php/core/partials/envloader.php";

/**
 * Session initialize
 */
session_start();

/**
 * Timezone is set to default (@env)
 */
date_default_timezone_set(env('time_zone'));

/**
 * Basix server adopt by codetazer and ctrx
 */
$basixserver = $_SERVER['HTTP_HOST'];
$req = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$req = trim($req, "/");
$rooth = getenv("rootpath") ?? "http://localhost:9999";
$rooth = str_ends_with($rooth, "/") ? substr($rooth, 0, -1) : $rooth;
$b_all = $basixserver . "/" . $req;
define("ctrx_param", strtolower($req));

$system = glob('app/php/core/partials/bin/*.php');
include "app/php/core/partials/be.php";

/**
 * Post request initialize
 */
$_POST = postdata();

foreach ($system as $k => $v) {
    include $v;
}

include "app/php/core/partials/ctrxc.php";

define("roothpath", getenv("roothpath"));

if ($req == "api") {
    json_response([
        "code" => getenv("success_code"),
        "message" => "CTRX framework by CodeYro"
    ]);
}

include "app/php/core/system/loader.php";

/**
 * This is backend endpoint
 */
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

        $_SESSION['basixs_current_be_ctrx'] = $newReq;
        defined("route") || define("ROUTE", rem_php($newReq));
        if (getenv("cross_origin_sharing") == "yes") {
            $allowAllOrigin = getenv("allow_all_origin");
            if ($allowAllOrigin == "yes") {
                header("Access-Control-Allow-Origin: *");
            } else {
                $allowed = \Classes\Cors::get_allowed_origin("string");
                if ($allowed) {
                    header("Access-Control-Allow-Origin: " . $allowed);
                }
            }
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
                    "message" => "Sorry, we are unable to share resources to '$origin'"
                ]);
                exit;
            }
        }
        if ($newReq == "ctrx_x_ctrql_request_authorized_ql") {
            include "app/php/core/system/ctrql.php";
            exit;
        }
        $is_in = isset($_REQUEST["ctrx_" . $reqmeth . "_" . $newReq]) ? $_REQUEST["ctrx_" . $reqmeth . "_" . $newReq] : null;
        if (! $is_in) {
            if (getenv("auto_routes") == "yes") {
                $newReqPHP = append_php($newReq);
                if (! file_exists("app/_controller/$newReqPHP")) {
                    ctrx_response(["code" => env('notfound_code'), "message" => "Controller '$newReq' not found.!"], 500);
                }
            } else {
                $upperReqMethod = strtoupper($reqmeth);
                ctrx_response(["code" => env('notfound_code'), "message" => "Route: ($upperReqMethod) '$newReq' not found"], 500);
            }
            include "app/_controller/$newReqPHP";
        }
        $route = append_php($is_in['route']);
        if (isset($is_in['middleware'])) {
            foreach ($is_in['middleware'] as $k => $v) {
                $mw = append_php($v);
                include "app/middleware/$mw";
            }
            if (! file_exists("app/_controller/$route")) {
                ctrx_response(["code" => env('notfound_code'), "message" => "Controller '$route' not found.!"], 500);
            }
        }
        include "app/_controller/$route";
    } catch (Throwable $e) {
        ctrx_response(["code" => error_code, "message" => $e->getMessage(), "trace" => $e->getTrace()], 500, $e);
    } catch (PDOException $e) {
        ctrx_response(["code" => error_code, "message" => $e->getMessage(), "trace" => $e->getTrace()], 500, $e);
    } catch (Exception $e) {
        ctrx_response(["code" => error_code, "message" => $e->getMessage(), "trace" => $e->getTrace()], 500, $e);
    } catch (InvalidArgumentException $e) {
        ctrx_response(["code" => error_code, "message" => $e->getMessage(), "trace" => $e->getTrace()], 500, $e);
    } finally {
        restore_error_handler();
    }
    exit;
} else {
    $_SESSION['ctrx_endpoint'] = "FE";
    $_SESSION['ctr_unique_request_id_x0015'] = ctr_generate_request_id();

    $csrfHashCode = encrypted_csrf_codetazer(25);
    $_SESSION[ctr_secure_key] = $csrfHashCode;

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
        include "app/php/core/partials/cx.php";
        $view_config = file_get_contents("views/config.json");
        $view_config = json_decode($view_config, true);
        $mainpage = $view_config['main_page'] ?? "main";
        $mainpage = append_php($mainpage);
        $mainnophp = rem_php($mainpage);
        $req = $req ? $req : $mainnophp;
        $_SESSION['basixs_current_fe_ctrx'] = $req;

        $feconfig = glob('views/app/config/*.php');
        foreach ($feconfig as $k => $v) {
            $vv = append_php($v);
            include $vv;
        }
        $is_in = $_REQUEST["ctrxfe_" . $req] ?? null;
        if ($is_in) {
            $mw = $is_in["middleware"] ?? null;
            $parent = $is_in["parent"] ?? null;
            if ($mw) {
                foreach ($mw as $k => $v) {
                    $phpfile = append_php($v);
                    $mdfile = "views/app/middleware/" . $phpfile;
                    if (! file_exists($mdfile)) {
                        throw new Exception("Middleware $v not found.!");
                    }
                    include $mdfile;
                }
            }
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
        $reqid = ctr_get_current_request_id();
        if (env_in_prod()) {
            $error = $e;
            if ($error) {
                $fulltrace = env("full_trace");
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
            $servererror = $view_config["prod_error_page"] ?? "dev_error";
            $servererror = append_php($servererror);
            $file =  "views/core/errors/$servererror";
            include $file;
            exit;
        } else {
            $trace = $e->getTrace();
            $fulltrace = env("full_trace");

            $all = [];
            foreach ($trace as $k => $v) {
                $file = $v['file'] ?? null;
                if (! $file) {
                    continue;
                }

                if ($fulltrace == "no" && str_contains($file, "\app\php\core")) {
                    continue;
                }
                $all[] = $v;
            }

            $error = [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $all
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
