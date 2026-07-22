<?php

/**
 * This is CTRX framework
 * Made by Tyrone Limen Malocon
 */
require_once 'vendor/autoload.php';
include_once "app/php/core/partials/envloader.php";
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
$rooth = env("rootpath") ?? "http://localhost:9999";
$rooth = trim($rooth, "/");
$b_all = $basixserver . "/" . $req;
$subdomain = env("subdomain") ?? null;
$subdomain = trim($subdomain, "/");
$trnsltn = $_GET['ctrx_translate'] ?? $_SESSION['ctrx_translate'] ?? null;
if (isset($_GET['ctrx_translate'])) {
    $_SESSION['ctrx_translate'] = $trnsltn;
}
if ($subdomain) {
    if (str_starts_with($req, $subdomain) && $req) {
        $expl = explode($subdomain, $req);
        $void = $expl[0] ?? null;
        $req = $expl[1] ?? null;
        if ($req) {
            $req = trim($req, "/");
        }
    }
}

define('mainpath', $subdomain ? $rooth . "/" . $subdomain : $rooth);
define("ctrx_param", strtolower($req));

$system = glob('app/php/core/partials/bin/*.php');
include_once "app/php/core/partials/be.php";

if (env("global_db_access") == "yes") {
    include_once "app/php/core/partials/backend.php";
}
/**
 * Post request initialize
 */
$_POST = postdata();

foreach ($system as $k => $v) {
    include $v;
}

include_once "app/php/core/partials/ctrxc.php";

define("roothpath", env("roothpath"));

if ($req == "api") {
    json_response([
        "code" => env("success_code"),
        "message" => "CTRX framework by CodeYro"
    ]);
}

include "app/php/core/system/loader.php";

/**
 * Ctrx Game for devs
 */
if (str_starts_with($req, "ctrxtools/game")) {
    \Classes\Ctrx::use_tool("app/php/core/system/ctrxgame.php", "ctrxtools/game");
    exit;
}

/**
 * This is backend endpoint
 */

if (str_starts_with($req, "api/")) {
    if (env('single_thread') && env('single_thread') == "yes") {
        if (isset($_COOKIE[ctrxc_ccookie_single_thread()])) {
            exit;
        }
    }
    $_COOKIE[ctrxc_ccookie_single_thread()] = ctrxc_ccookie_single_thread();
    include_once "app/php/core/partials/backend.php";

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    });
    try {
        $_SESSION['ctrx_endpoint'] = "BE";

        $serve = "";

        $_SESSION['ctr_unique_request_id_x0015'] = ctr_generate_request_id();

        $req = strtolower($req);
        if (str_starts_with($req, "api/")) $serve = "api";
        $newReq = "";
        if ($serve == "api") $newReq = str_replace("api/", "", $req);
        $reqmeth = strtolower(request_method());

        $_SESSION['basixs_current_be_ctrx'] = $newReq;
        defined("route") || define("ROUTE", rem_php($newReq));

        \Classes\Ctrx::include_all_autoFiles();

        if (env("cross_origin_sharing") == "yes") {
            $allowAllOrigin = env("allow_all_origin");
            if ($allowAllOrigin == "yes") {
                header("Access-Control-Allow-Origin: *");
            } else {
                $allowed = \Classes\Cors::get_allowed_origin("string");
                if ($allowed) {
                    header("Access-Control-Allow-Origin: " . $allowed);
                }
            }
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: " . env("allowed_headers"));
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
            if (env("auto_routes") == "yes") {
                $newReqPHP = append_php($newReq);
                if (! \Classes\Ctrx::file_exists_strict("app/_controller/$newReqPHP")) {
                    ctrx_response(["code" => env('notfound_code'), "message" => "Controller '$newReq' not found.!"], 500);
                }
            } else {
                $upperReqMethod = strtoupper($reqmeth);
                ctrx_response(["code" => env('notfound_code'), "message" => "Controller: ($upperReqMethod) '$newReq' not found"], 500);
            }
            echo \Classes\Ctrx::file_exists_strict("app/_controller/$newReqPHP");
            exit;
            include "app/_controller/$newReqPHP";
            throw new Exception("Controller endpoint not reached");
        }
        $route = append_php($is_in['route']);
        if (isset($is_in['middleware'])) {
            foreach ($is_in['middleware'] as $k => $v) {
                $mw = append_php($v);
                include "app/middleware/$mw";
            }
            if (! \Classes\Ctrx::file_exists_strict("app/_controller/$route")) {
                ctrx_response(["code" => env('notfound_code'), "message" => "Controller '$route' not found.!"], 500);
            }
        } else {
            if (! \Classes\Ctrx::file_exists_strict("app/_controller/$route")) {
                ctrx_response(["code" => env('notfound_code'), "message" => "Controller '$route' not found.!"], 500);
            }
        }
        include "app/_controller/$route";
        throw new Exception("Controller endpoint not reached");
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
        $view_config = file_get_contents("views/fe_config.json");
        $view_config = json_decode($view_config, true);
        $mainpage = $view_config['main_page'] ?? "main";
        $mainpage = append_php($mainpage);
        $mainnophp = rem_php($mainpage);
        $req = $req ? $req : $mainnophp;
        if ($_GET) {
            $phar = array_as_param($_GET);
            $_SESSION['basixs_current_fe_ctrx'] = $req . $phar;
        } else {
            $_SESSION['basixs_current_fe_ctrx'] = $req;
        }
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
                    if (! \Classes\Ctrx::file_exists_strict($mdfile)) {
                        throw new Exception("Middleware $v not found.!");
                    }
                    include $mdfile;
                }
            }
        }

        $page = append_php($req);
        $fullpath = "views/pages/" . $page;

        $maintnanc = env("system_maintenance") ?? "no";
        if ($maintnanc != "no") {
            \Classes\Ctrx::systemMaintenance();
        }

        if (! defined("prev_page")) define("prev_page", prev_page());

        if ($req == "ctrx/logout") {
            $lgt = fe_config("default_logout");
            $page = $_GET['page'] ?? \Classes\Ctrx::get_logout_page() ?? $lgt ?? "/";
            \Classes\Ctrx::delete_user_data();
            \Classes\Ccookie::delete("ctrx_user_logout_page");
            redirect($page);
        }
        /**
         * Ctrx DB tools for database management
         */
        if (str_starts_with($req, "ctrxtools/database")) {
            $data = \Classes\Ctrx::get_user_data();
            if (! $data) {
                echo "<b style='color:red;'>You are not authorize to accesss this page</b>";
                redirect("ctrx/logout", "page", 2);
                return;
            }
            $userTools = $data['access_ctrx_tools'] ?? null;
            if (empty($userTools) || ! in_array("database", $userTools)) {
                \Classes\Ctrx::forbidden_page();
                return;
            }
            Classes\Ctrx::use_database_management();
        }

        /**
         * Ctrx DB tools for database management
         */
        if (str_starts_with($req, "ctrxtools/logs")) {
            $data = \Classes\Ctrx::get_user_data();
            if (! $data) {
                echo "<b style='color:red;'>You are not authorize to accesss this page</b>";
                redirect("ctrx/logout", "page", 2);
                return;
            }
            $userTools = $data['access_ctrx_tools'] ?? null;
            if (empty($userTools) || ! in_array("logs", $userTools)) {
                \Classes\Ctrx::forbidden_page();
                return;
            }
            Classes\Ctrx::use_logs_tools();
        }
        /**
         * Ctrx DB tools for import export
         */
        if (str_starts_with($req, "ctrxtools/data")) {
            $data = \Classes\Ctrx::get_user_data();
            if (! $data) {
                echo "<b style='color:red;'>You are not authorize to accesss this page</b>";
                redirect("ctrx/logout", "page", 2);
                return;
            }
            $userTools = $data['access_ctrx_tools'] ?? null;
            if (empty($userTools) || ! in_array("data", $userTools)) {
                \Classes\Ctrx::forbidden_page();
                return;
            }
            \Classes\Ctrx::use_db_tools();
        }
        /**
         * Ctrx Translation tools for import export
         */
        if (str_starts_with($req, "ctrxtools/roles")) {
            $data = \Classes\Ctrx::get_user_data();
            if (! $data) {
                echo "<b style='color:red;'>You are not authorize to accesss this page</b>";
                redirect("ctrx/logout", "page", 2);
                return;
            }
            $userTools = $data['access_ctrx_tools'] ?? null;
            if (empty($userTools) || ! in_array("roles", $userTools)) {
                \Classes\Ctrx::forbidden_page();
                return;
            }
            \Classes\Ctrx::use_roles_tools();
        }
        /**
         * Ctrx Translation tools for import export
         */
        if (str_starts_with($req, "ctrxtools/translations")) {
            $data = \Classes\Ctrx::get_user_data();
            $logoutPage = \Classes\Ctrx::get_logout_page() ?? "/";
            if (! $data) {
                echo "<b style='color:red;'>You are not authorize to accesss this page</b>";
                redirect($logoutPage, "page", 2);
                return;
            }
            $userTools = $data['access_ctrx_tools'] ?? null;
            if (empty($userTools) || ! in_array("translations", $userTools)) {
                \Classes\Ctrx::forbidden_page();
                return;
            }
            \Classes\Ctrx::use_translate_tools();
        }

        /**
         * Ctrx Tools page
         */
        if (str_starts_with($req, "ctrxtools")) {
            $data = \Classes\Ctrx::get_user_data();
            $logoutPage = \Classes\Ctrx::get_logout_page() ?? "/";
            if (! $data) {
                echo "<b style='color:red;'>You are not authorize to accesss this page</b>";
                redirect($logoutPage, "page", 2);
                return;
            }
            $userTools = \Classes\Ctrx::get_access_tools();;
            if (empty($userTools)) {
                \Classes\Ctrx::forbidden_page();
            }
            include_once "app/php/core/system/toolpicker.php";
            \Classes\Ctrx::ctrx_save_previous_pages();
            exit;
        }

        if (!\Classes\Ctrx::file_exists_strict($fullpath)) {
            $errorpage = $view_config["page_not_found"] ?? "404";
            \Classes\Ctrx::page404($errorpage, false);
            exit;
        }

        include $fullpath;
        if (env('debugger') == "yes") {
            include_once "views/core/partials/system/dev.php";
        }
        \Classes\Ctrx::ctrx_save_previous_pages();
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
                ctrx_log($e_msg . " " . $fandl . "Trace: " . $e_error, "frontend", $reqid);
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
