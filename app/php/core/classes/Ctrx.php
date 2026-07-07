<?php

namespace Classes;

class Ctrx
{
    private static string|null $xrateMessage = null;

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
        $ip = $_SERVER['REMOTE_ADDR'];
        $dir = "app/php/core/partials/dir";
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $window = $seconds;
        $org = $route;
        $route = ! $route ? current_be() : "ctzr_" . $route;
        $file = $dir . '/ratelimit_' . md5($route . '_' . $ip);
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (time() - $data['start'] > $window) {
                $data = ['count' => 0, 'start' => time()];
            }
        } else {
            $data = ['count' => 0, 'start' => time()];
        }

        $data['route'] = $org;
        $data['ctr'] = $route;
        $data['count']++;
        $data['left'] = $limit - intval($data['count']);
        $data['limit'] = $limit;
        $data['seconds'] = $seconds;
        $remaining = max(0, $limit - $data['count']);
        $reset = $data['start'] + $window;

        header("X-RateLimit-Limit: $limit");
        header("X-RateLimit-Remaining: $remaining");
        header("X-RateLimit-Reset: $reset");

        if ($data['count'] > $limit) {
            header('Content-Type: application/json');
            http_response_code(429);
            header('Retry-After: ' . ($window - (time() - $data['start'])));
            $msg = ! self::$xrateMessage ? 'Request limit exceed, Please try again later.' : self::$xrateMessage;
            echo json_encode([
                'code' => 429,
                'message' => $msg,
                'error' => 'Request limit exceeded',
                'limit' => $limit,
                'window' => $window,
                'retry_after' => $window - (time() - $data['start'])
            ]);
            exit;
        }
        return file_put_contents($file, json_encode($data));
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
        if (file_exists($file)) {
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

    public static function set_user_role(string|int $role, int $duration = 60): void
    {
        \Classes\Ccookie::add("ctrx_user_role", $role, $duration);
    }

    public static function get_user_role(string|int $role): null|int|string
    {
        $role = \Classes\Ccookie::get("ctrx_user_role");
        if (! $role) {
            return null;
        }
        return $role;
    }

    public static function delete_user_role(): void
    {
        \Classes\Ccookie::delete("ctrx_user_role");
    }

    public static function delete_user_data(): void
    {
        \Classes\Ccookie::delete("ctrx_user_data");
    }

    public static function reset_all_user_data(): void
    {
        self::delete_user_data();
        self::delete_user_role();
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

    public static function set_user_data(array $data, int $duration = 60): void
    {
        $ctrxdata = \Classes\Ccookie::get("ctrx_user_data");
        if ($ctrxdata) {
            $ctrxdata = [...$ctrxdata, ...$data];
        } else {
            $ctrxdata = $data;
        }
        \Classes\Ccookie::add("ctrx_user_data", $ctrxdata, $duration);
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

    public static function use_database_management(string|null $backpage = null, $exit = true)
    {
        self::resetBackend();
        $backRoute = $backpage ?? previous_page();
        if ($backRoute) {
            include "app/php/core/system/dtbs.php";
        } else {
            include "app/php/core/system/dtbs.php";
        }
        if ($exit) exit;
    }

    public static function forbidden_page(string|null $backpage = null, $exit = true)
    {
        $backRoute = $backpage ?? previous_page();
        if ($backRoute) {
            $backRoute = str_starts_with($backRoute, "/") ? $backRoute : "/" . $backRoute;
            extract([
                "backpage" => $backRoute
            ]);
        }
        if (! defined("prev_page")) define("prev_page", prev_page());
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
}
