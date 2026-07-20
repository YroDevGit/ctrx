<?php

namespace Classes;

use Error;
use Exception;

class Ctrx
{
    private static string|null $xrateMessage = null;
    private static $lastDuration = 60;

    public static function generate_token(string|int $text, string|null $key = null, int $length = 22): string
    {
        if (! $key) {
            return substr(md5(date("ymdHisA") . $text . env("hash_secret")), 0, $length);
        }
        return substr(md5(date("ymdHisA") . $text . $key), 0, $length);
    }

    private static function headers(string|null $key = null, $ucwords = false)
    {
        if (is_null($key)) {
            return server_headers($key);
        } else {
            $key = strtolower($key);
            if ($ucwords) {
                return server_headers($key);
            }
            return server_headers($key);
        }
    }

    static function validate_csrf()
    {
        $post = self::headers("X_CSRF_CTR_Token") ?? null;
        if (! $post) {
            Response::code(unauthorized_code)->message("csrf not found")->data(self::headers())->send(unauthorized_code);
        }
        if ($post !== csrf_token()) {
            Response::code(unauthorized_code)->message("Unauthorize request (csrf)")->send(unauthorized_code);
        }
    }

    /**
     * Limit the request to backend
     * @param int $limit : max request
     * @param int $seconds: max request per seconds
     * @param string $route: unique route/name for this limit
     */
    public static function x_rate_limit(int $limit = 100, int $seconds = 60, string|null $route = "")
    {
        return self::ctrratelimit($limit, $seconds, $route);
    }

    public static function x_rate_limit_message(string|null $message)
    {
        if ($message) {
            self::$xrateMessage = $message;
        }
    }

    public static function throttle_limit_message(string|null $message)
    {
        if ($message) {
            self::$xrateMessage = $message;
        }
    }

    public static function setMessage(string $message)
    {
        if ($message) {
            self::$xrateMessage = $message;
        }
        return new self;
    }

    public static function x_rate_limit_global(int $limit = 100, int $seconds = 60)
    {
        return self::x_rate_limit($limit, $seconds, "all");
    }

    public static function x_rate_limit_route(int $limit = 100, int $seconds = 60)
    {
        return self::x_rate_limit($limit, $seconds);
    }

    public static function throttle(int $limit, int $seconds = 60, string $route = "")
    {
        return self::x_rate_limit($limit, $seconds, $route);
    }

    public static function x_rate_details(string|null $route = "")
    {
        return self::ctrratedetails($route);
    }

    public static function x_rate_details_global()
    {
        return self::x_rate_details("all");
    }

    private static function ctrratelimit($limit = 100, $seconds = 60, $route = "")
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $dir = "app/php/core/partials/dir";

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $window = (int) $seconds;
        $org = $route;
        $route = empty($route) ? current_be() : "ctzr_" . $route;
        $file = $dir . '/ratelimit_' . md5($route . '_' . $ip);

        if (mt_rand(1, 50) === 5) {
            foreach (glob($dir . '/ratelimit_*') as $f) {
                if (@filemtime($f) + $window < time()) {
                    @unlink($f);
                }
            }
        }

        $fp = fopen($file, 'c+');

        if (!$fp) {
            return false;
        }

        flock($fp, LOCK_EX);

        rewind($fp);
        $contents = stream_get_contents($fp);

        $data = json_decode($contents, true);

        if (!is_array($data)) {
            $data = [
                'count' => 0,
                'start' => time()
            ];
        }

        if ((time() - $data['start']) > $window) {
            $data = [
                'count' => 0,
                'start' => time()
            ];
        }

        $data['route'] = $org;
        $data['ctr'] = $route;
        $data['count']++;
        $data['left'] = max(0, $limit - $data['count']);
        $data['limit'] = $limit;
        $data['seconds'] = $window;

        $remaining = max(0, $limit - $data['count']);
        $reset = $data['start'] + $window;

        header("X-RateLimit-Limit: {$limit}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: {$reset}");

        if ($data['count'] > $limit) {
            flock($fp, LOCK_UN);
            fclose($fp);

            header('Content-Type: application/json');
            http_response_code(429);
            header('Retry-After: ' . max(0, $window - (time() - $data['start'])));

            $msg = self::$xrateMessage ?: 'Request limit exceeded. Please try again later.';

            echo json_encode([
                'code'        => 429,
                'message'     => $msg,
                'error'       => 'Request limit exceeded',
                'limit'       => $limit,
                'window'      => $window,
                'retry_after' => max(0, $window - (time() - $data['start']))
            ]);

            exit;
        }

        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data));
        fflush($fp);

        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    public static function file_exists_strict(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $dir = dirname($path);
        $file = basename($path);


        return in_array($file, scandir($dir), true);
    }

    private static function ctrratedetails($route = "")
    {
        $dir = "app/php/core/partials/dir";
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        $window = 60;
        $limit = 100;
        $route = ! $route ? current_be() : "ctzr_" . $route;
        $file = $dir . '/ratelimit_' . md5($route . '_' . $ip);
        if (\Classes\Ctrx::file_exists_strict($file)) {
            $data = json_decode(file_get_contents($file), true);
            $window = $data['seconds'] ?? $window;
            $limit = $data['limit'] ?? $limit;
            if (time() - $data['start'] > $window) {
                $data = ['count' => 0, 'start' => time()];
            }
        } else {
            $data = ['count' => 0, 'start' => time()];
        }
        $remaining = max(0, $limit - $data['count']);
        $reset = $data['start'] + $window;
        $data['reset'] = $reset;
        return $data;
    }

    public static function set_logged_in(bool $logged_in, int $duration = 60): void
    {
        if (! $logged_in) {
            \Classes\Ccookie::delete("ctrx_logged_in");
        } else {
            \Classes\Ccookie::add("ctrx_logged_in", $logged_in ? "Y" : "N", $duration);
        }
    }

    public static function is_logged_in(): bool
    {
        $logged_in = \Classes\Ccookie::get("ctrx_logged_in");
        if (! $logged_in) {
            return false;
        }
        return true;
    }

    public static function set_user_role(string|int $role, $autoAdmin = true): void
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if (! $ctrxdata) {
            throw new Exception("Access tools: invalid call without user data");
        }

        if (env('database') && $role != "admin") {
            if (\Classes\DB::tableExists("ctrx_roles") && \Classes\DB::tableExists("ctrx_roles_access")) {
                $find = \Classes\DB::findOne("ctrx_roles", ["role_name" => $role]);
                if (! $find) {
                    throw new Exception("set_user_role: Invalid role '$role'");
                }
            }
        }

        $ctrxdata = [...$ctrxdata, "ctrx_user_role" => $role];

        \Classes\Ccookie::add("ctrx_user_data", $ctrxdata, self::$lastDuration);

        if ($role == "admin" && $autoAdmin) {
            self::access_tools();
        }
    }

    public static function set_logout_page(string|int $role): void
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if (! $ctrxdata) {
            throw new Exception("Access tools: invalid call without user data");
        }

        $ctrxdata = [...$ctrxdata, "ctrx_logout_page" => $role];

        \Classes\Ccookie::add("ctrx_user_data", $ctrxdata, self::$lastDuration);
    }

    public static function get_logout_page(): null|int|string
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if (! $ctrxdata) {
            return null;
        } else {
            if (isset($ctrxdata['ctrx_logout_page'])) {
                return $ctrxdata['ctrx_logout_page'] ?? null;
            }
        }
        return null;
    }

    public static function get_user_role(): null|int|string
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if (! $ctrxdata) {
            return null;
        } else {
            if (isset($ctrxdata['ctrx_user_role'])) {
                return $ctrxdata['ctrx_user_role'] ?? null;
            }
        }
        return null;
    }

    public static function delete_user_data(): void
    {
        \Classes\Ccookie::delete("ctrx_user_data");
    }

    public static function reset_all_user_data(): void
    {
        self::delete_user_data();
        self::set_logged_in(false);
    }

    public static function validate_user_role(int|string|null $role)
    {
        $ctrxrole = \Classes\Ccookie::get("ctrx_user_role");
        if (! $ctrxrole) {
            return false;
        }

        if ($ctrxrole === $role) {
            return true;
        }
        return false;
    }

    public static function set_user_data(array $data, int $duration = 1440): void
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if ($ctrxdata) {
            $ctrxdata = [...$ctrxdata, ...$data];
        } else {
            $ctrxdata = $data;
        }
        self::$lastDuration = $duration;
        \Classes\Ccookie::add("ctrx_user_data", $ctrxdata, $duration);
    }

    /**
     * requirements: set_user_data
     * Access ctrx admin tools
     */
    public static function access_tools(string ...$tools)
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if (! $ctrxdata) {
            throw new Exception("Access tools: invalid call without user data");
        }
        if (! $tools) {
            $extraData = ["data", "translations", "database", "roles"];
            $ctrxdata = [...$ctrxdata, "access_ctrx_tools" => $extraData];
        } else {
            $ctrxdata = [...$ctrxdata, "access_ctrx_tools" => $tools];
        }
        \Classes\Ccookie::add("ctrx_user_data", $ctrxdata, self::$lastDuration);
        return true;
    }

    /**
     * requirements: access_tools
     * Get tool that can be accessed by current user
     */
    public static function get_access_tools()
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if (! $ctrxdata) {
            return [];
        } else {
            if (isset($ctrxdata['access_ctrx_tools'])) {
                return $ctrxdata['access_ctrx_tools'];
            }
        }
        return [];
    }

    public static function extend_user_data($duration)
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if ($ctrxdata) {
            \Classes\Ccookie::add("ctrx_user_data", $ctrxdata, $duration);
            return true;
        }
        return false;
    }

    public static function get_user_data(string|int $key = "*")
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if (! $ctrxdata) return null;
        if ($key == "*") {
            return $ctrxdata;
        }
        return isset($ctrxdata[$key]) ? $ctrxdata[$key] : null;
    }

    public static function role_filtering(callable|null $execute = null)
    {
        $role = null;
        $currPage = current_page();
        $roleFilt = fe_config("role_filtering");
        $UserRole = self::get_user_role();
        if ($roleFilt != "yes" || $roleFilt == null) {
            return;
        }
        if (! env('database')) {
            return;
        }
        if ($UserRole == "admin") {
            return;
        }
        if ($currPage == "ctrx/logout") {
            return;
        }
        if (str_starts_with($currPage, "ctrxtools")) {
            return;
        }
        if (! \Classes\DB::tableExists("ctrx_roles")) {
            return;
        }
        if (! $role) {
            $role = $UserRole ?? "public";
        }

        $query = "SELECT r.role_name, r.`description`, r.created_at, r.updated_at, a.route, a.role_id FROM ctrx_roles r, ctrx_roles_access a WHERE r.id = a.role_id AND r.role_name = ? and a.route = ? and a.has_access = 1";
        $param = [$role, $currPage];
        $result = \Classes\DB::query($query, $param);

        if (! $result) {
            if (is_null($execute)) {
                if (self::has_user_data()) {
                    self::forbidden_page();
                } else {
                    self::forbidden_page(self::get_logout_page());
                }
            } else if (is_callable($execute)) {
                $execute($result);
            }
        }
        return true;
    }

    public static function has_user_data(): bool
    {
        if (\Classes\Ccookie::get("ctrx_user_data")) {
            return true;
        }
        return false;
    }

    public static function use_db_tools(string|null $backpage = null, $exit = true)
    {
        $backRoute = $backpage ?? previous_page();
        if ($backRoute) {
            include "app/php/core/system/tools.php";
        } else {
            include "app/php/core/system/tools.php";
        }
        if ($exit) exit;
    }

    public static function use_tool(string $file, $path)
    {
        self::resetBackend();
        self::include_all_autoFiles();
        $_SESSION['basixs_current_fe_ctrx'] = $path;
        $prev = self::get_prev_path_toSave();
        if (! defined("prev_page")) define("prev_page", prev_page());
        include $file;
        self::ctrx_save_previous_pages($prev);
        return true;
    }

    public static function removeCharacter(string $character, int $index)
    {
        return substr($character, $index);
    }

    public static function ctrx_save_previous_pages(string $previous_page = null)
    {
        $curr = current_page(true);
        if ($curr == self::box1()) {
            //Tyrone Lee Emz
        } else {
            self::box2(self::box1());
            self::box1($curr);
        }
    }

    public static function ctrx_getPreviousPage()
    {
        $curr = current_page(true);
        if ($curr == self::box1()) {
            return self::box2();
        } else {
            return self::box1();
        }
    }

    private static function box1($data = null)
    {
        if ($data) {
            $_SESSION['cTrx_pReviOus_paGee_basixs112100514'] = $data;
            return $data;
        } else {
            return $_SESSION['cTrx_pReviOus_paGee_basixs112100514'] ?? "/";
        }
    }

    private static function box2($data = null)
    {
        if ($data) {
            $_SESSION['cTrx_pReviOus_paGee_basixs112100515'] = $data;
            return $data;
        } else {
            return $_SESSION['cTrx_pReviOus_paGee_basixs112100515'] ?? "/";
        }
    }

    public static function use_translate_tools(string|null $backpage = null, $exit = true)
    {
        self::resetBackend();
        $backRoute = $backpage ?? previous_page();
        if ($backRoute) {;
            include "app/php/core/system/trnsltn.php";
        } else {
            include "app/php/core/system/trnsltn.php";
        }
        if ($exit) exit;
    }

    public static function use_roles_tools(string|null $backpage = null, $exit = true)
    {
        self::resetBackend();
        $backRoute = $backpage ?? previous_page();
        if ($backRoute) {;
            include "app/php/core/system/ctrxroles.php";
        } else {
            include "app/php/core/system/ctrxroles.php";
        }
        if ($exit) exit;
    }

    public static function use_database_management(string|null $backpage = null, $exit = true)
    {
        //self::resetBackend();
        $backRoute = $backpage ?? previous_page();
        if ($backRoute) {
            include_once "app/php/core/system/dtbs.php";
        } else {
            include_once "app/php/core/system/dtbs.php";
        }
        if ($exit) exit;
    }

    public static function forbidden_page(string|null $backpage = null, $exit = true)
    {
        if (! defined("prev_page")) define("prev_page", prev_page());
        $backRoute = $backpage ?? prev_page ?? "/";
        if ($backRoute) {
            $backRoute = str_starts_with($backRoute, "/") ? $backRoute : "/" . $backRoute;
            extract([
                "backpage" => $backRoute
            ]);
        }
        include "views/core/errors/forbidden.php";
        if ($exit) exit;
    }

    public static function resetBackend()
    {
        unset($_SESSION['basixs_current_be_ctrx']);
    }

    public static function ctrx_prvPage($withParam = false)
    {
        $url = $_SESSION['cTrx_pReviOus_paGee_basixs112100514'];
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            if ($withParam) {
                return $path . "?" . $query;
            } else {
                return $path;
            }
        } else {
            return $path;
        }
    }

    public static function page404($errorpage, $exit = true)
    {
        $errorpage = append_php($errorpage);
        if (! defined("prev_page")) define("prev_page", prev_page());
        include "views/core/errors/" . $errorpage;
        if ($exit) {
            exit;
        }
    }

    public static function systemMaintenance($variables = [], $page = "maintenance", $exit = true)
    {
        $errorpage = append_php($page);
        if (! defined("prev_page")) define("prev_page", prev_page());
        if ($variables) {
            extract($variables);
        }
        include "views/core/main/" . $errorpage;
        if ($exit) {
            exit;
        }
    }

    public static function get_prev_path_toSave()
    {
        $prevPath = "/";

        if ($_GET) {
            $arr = [];
            foreach ($_GET as $kk => $vv) {
                $arr[] = $kk . "=" . $vv;
            }
            $prevPath = current_page() . "?" . implode("&", $arr);
        } else {
            $prevPath = current_page();
        }
        $prevPath = str_starts_with($prevPath, "/") ? $prevPath : "/" . $prevPath;
        return $prevPath;
    }

    public static function include_all_autoFiles()
    {
        $beconfig = glob('app/config/*.php');
        foreach ($beconfig as $k => $v) {
            if ($v == "app/config/storage_config.php" || $v == "app\config\storage_config.php") continue;
            if ($v == "app/config/db_tools.php" || $v == "app\config\db_tools.php") continue;
            if ($v == "app/config/translations.php" || $v == "app\\config\\translations.php") continue;
            if ($v == "app/config/ql.php" || $v == "app\config\ql.php") continue;
            if ($v == "app/config/ctr_db.php" || $v == "app\config\ctr_db.php") continue;
            include_once $v;
        }
    }

    public static function getPastDueCronJobs()
    {
        $tbl = 'ctrx_cron';
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM `" . $tbl . "` 
                WHERE status = 'active' 
                AND next_run IS NOT NULL 
                AND next_run <= ? 
                ORDER BY next_run ASC";

        return \Classes\DB::query($sql, [$now]);
    }

    public static function selfCurl($url, $headers = [], $data = [])
    {
        $head = [
            'Content-Type: application/json',
            ...$headers
        ];

        $root = env('rootpath');
        $ch = curl_init("$root/$url");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $head,
            CURLOPT_POSTFIELDS => json_encode($data ?? []),
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            die(curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }

    public static function getCronResponse(string $controller, $data = [])
    {
        $controller = trim($controller, "/");
        if (! str_ends_with($controller, ".php")) {
            $controller = $controller . ".php";
        }
        $file = "app/_controller/" . $controller;
        if (! is_file($file)) {
            throw new Error("$file not found");
        }

        if ($data) {
            extract($data);
        }
        include $file;
        $content = file_get_contents($file);

        return $content;
    }
}
