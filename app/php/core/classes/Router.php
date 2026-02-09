<?php

namespace Classes;

use Error;
use Exception;

class Router
{

    private static $parent = "";
    private string $route = "";
    private string $group = "";
    private array $arr = [];

    public function __construct(string $var = null, $group = false, array $arr = [], $keys = [])
    {
        if ($group) {
            $this->group = $var;
            $this->arr = $arr;
        } else $this->route = $var;
    }

    public static function post(string $route)
    {
        $pref = self::getGlobalPrefix();
        self::checkRoutes($route);
        $key = 'ctrx_post_' . $route;
        if($pref){
            $key = 'ctrx_post_' .$pref."/".$route;
        }
        $_REQUEST[$key] = ["route" => $route, "method" => "post", "intro" => 'ctrx_post_'];
        return new self($key);
    }

    public static function get(string $route)
    {
        $pref = self::getGlobalPrefix();
        self::checkRoutes($route);
        $key = 'ctrx_get_' . $route;
        if($pref){
            $key = 'ctrx_get_' .$pref."/".$route;
        }
        $_REQUEST[$key] = ["route" => $route, "method" => "get", "intro" => 'ctrx_get_'];
        return new self($key);
    }

    public static function put(string $route)
    {
        $pref = self::getGlobalPrefix();
        self::checkRoutes($route);
        $key = 'ctrx_put_' . $route;
        if($pref){
            $key = 'ctrx_put_' .$pref."/".$route;
        }
        $_REQUEST[$key] = ["route" => $route, "method" => "put", "intro" => 'ctrx_put_'];
        return new self($key);
    }

    public static function delete(string $route)
    {
        $pref = self::getGlobalPrefix();
        self::checkRoutes($route);
        $key = 'ctrx_delete_' . $route;
        if($pref){
            $key = 'ctrx_delete_' .$pref."/".$route;
        }
        $_REQUEST[$key] = ["route" => $route, "method" => "delete", "intro" => 'ctrx_delete_'];
        return new self($key);
    }

    private static function checkRoutes(string $route)
    {
        $route = append_php($route);
        if (! file_exists("app/_controller/" . $route)) {
            throw new Exception("Controller $route not found.!");
        }
    }

    public function intro(string ...$middleware)
    {
        return $this->middleware(...$middleware);
    }

    public function middleware(string ...$middleware)
    {
        foreach ($middleware as $k => $v) {
            $file = append_php($v);
            if (! file_exists("app/middleware/$file")) {
                throw new Error("middleware '$v' not found.!");
            }
        }
        $key = $this->route;
        if ($key) {
            $_REQUEST[$key]["middleware"] = [...$middleware];
            return $this;
        }

        foreach ($this->arr as $kf => $vf) {
            foreach ($vf as $k => $v) {
                $kk = strtolower($k);
                $key = "ctrx_" . $kk . "_" . $v;
                $_REQUEST[$key]['middleware'] = [...$middleware];
            }
        }
        return $this;
    }


    public function parent(string $parent)
    {
        $pref = $this->getGlobalPrefix();
        $parent = trim($parent, "/");
        $key = $this->route;
        if ($key) {
            $data = $_REQUEST[$key];
            unset($_REQUEST[$key]);
            //continue
        }
        $newarr = [];
        foreach ($this->arr as $kf => $vf) {
            foreach ($vf as $k => $v) {
                $kk = strtolower($k);
                $key = "ctrx_" . $kk . "_" . $v;
                $data = $_REQUEST[$key];
                $route = $data['route'];
                $rename = isset($data['rename']) ? $data['rename'] : $route;
                unset($_REQUEST[$key]);
                if ($pref) {
                    $_REQUEST["ctrx_" . $kk . "_" . $pref . "/" . $parent . "/" . $rename] = $data;
                    $newarr[$kk] = $pref . "/" . $parent . "/" . $rename;
                } else {
                    $_REQUEST["ctrx_" . $kk . "_" . $parent . "/" . $v] = $data;
                    $newarr[$kk] = $parent . "/" . $v;
                }
            }
        }
        $this->arr = $newarr;
        return $this;
    }

    private static function getGlobalPrefix()
    {
        return $_REQUEST["ctrx_global_prefix"] ?? null;
    }

    static function group(array ...$routes)
    {
        if(! $routes){
            return;
        }
        $arr = [];
        $pref = self::getGlobalPrefix();
        foreach ($routes as $fk => $vk) {
            if(! $vk) continue;
            foreach ($vk as $k => $v) {
                if (is_string($v)) {
                    $newvexpl = explode(">>", $v);
                    $newv = isset($newvexpl[0]) ? trim($newvexpl[0]) : null;
                    $rename = isset($newvexpl[1]) ? trim($newvexpl[1]) : null;
                    $rename = $rename ? $rename : $newv;
                    self::checkRoutes($newv);
                    $key = strtolower($k);
                    $newkey = "ctrx_" . $key . "_" . $rename;
                    if ($pref) {
                        $newkey = "ctrx_" . $key . "_" . $pref . "/" . $rename;
                        $_REQUEST[$newkey] = ["route" => $newv, "method" => $key, "intro" => "ctrx_" . $key . "_", "rename" => $rename];
                        $arr[] = [strtolower($key) => strtolower($pref . "/" . $rename)];
                    } else {
                        $_REQUEST[$newkey] = ["route" => $newv, "method" => $key, "intro" => "ctrx_" . $key . "_", "rename" => $rename];
                        $arr[] = [strtolower($key) => strtolower($rename)];
                    }
                } else if (is_array($v)) {
                    $first = $v[0] ?? null;
                    $sec = $v[1] ?? $first;
                    if (! $first && ! $sec) {
                        throw new Exception("Route pattern error");
                    }
                    $key = strtolower($k);
                    self::checkRoutes($first);
                    if ($pref) {
                        $newkey = "ctrx_" . $key . "_" . $pref . "/" . $sec;
                        $_REQUEST[$newkey] = ["route" => $first, "method" => $key, "intro" => "ctrx_" . $key . "_"];
                        $arr[] = [strtolower($key) => strtolower($pref . "/" . $sec)];
                    } else {
                        $newkey = "ctrx_" . $key . "_" . $sec;
                        $_REQUEST[$newkey] = ["route" => $first, "method" => $key, "intro" => "ctrx_" . $key . "_"];
                        $arr[] = [strtolower($key) => strtolower($sec)];
                    }
                }
            }
        }
        $unique = bin2hex(random_bytes(10));
        return new self($unique, true, $arr);
    }
}
