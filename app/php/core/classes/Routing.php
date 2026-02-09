<?php

namespace Classes;

use Classes\Response;
use Exception;
use Throwable;

class Routing
{
    public static function in_route(string $route, callable $func)
    {
        $current = current_be();
        $current = trim($current);
        $route = trim($route);
        if (strtolower($current) == strtolower($route)) {
            $func();
        }
    }

    public static function use_middleware(string|null $middleware, array|string $routes){
        if(!is_string($middleware)){
            throw new Exception("middleware should be a string");
        }
        if(! $middleware){
            return false;
        }
        $ep = ctr_endpoint();
        if($ep == "FE"){
            self::group_page($routes, fn()=>use_middleware($middleware));
        }else{
            if(is_string($routes)){
                self::in_route($routes, fn()=>use_middleware($middleware));
            }
            else if(is_array($routes)){
                self::route_middleware($routes, $middleware);
            }
        }
    }

    public static function route_middleware(array $routes, string $midleware, $included = true)
    {
        self::route_filtering($routes, fn()=>use_middleware($midleware), $included);
    }


    public static function api_group(array $api, callable $callable)
    {
        $arr = [];
        foreach ($api as $a) {
            if (str_contains($a, "/*")) {
                $exp = explode("/*", $a);
                $parent = $exp[0];
                self::api_group(ctr_get_routes("api/" . $parent), $callable);
                continue;
            }
            $arr[] = "api/" . $a;
        }
        return self::group_route($arr, $callable);
    }

    private static function route_filtering(array $routes, callable $func, $included = true)
    {
        if (! $routes) {
            return false;
        }
        foreach ($routes as $r) {
            if (str_contains($r, "/*")) {
                $exp = explode("/*", $r);
                $parent = $exp[0];
                self::route_filtering(ctr_get_routes($parent), $func, $included);
                continue;
            }
            $path = substr($r, -4) == ".php" ? $r : $r . ".php";
            if (! file_exists("_backend/_app/_routes/$path")) {
                Response::code(notfound_code)->message("In group route, backend route $r not found.!")->send(notfound_code);
            }
        }
        $current = current_be();
        if ($included) {
            if (in_array($current, $routes)) {
                try {
                    $func();
                } catch (Throwable $e) {
                    throw new Exception($e->getMessage());
                }
            }
        } else {
            if (! in_array($current, $routes)) {
                try {
                    $func();
                } catch (Throwable $e) {
                    throw new Exception($e->getMessage());
                }
            }
        }
        return true;
    }

    public static function group_route(array $routes, callable $func)
    {
        return self::route_filtering($routes, $func);
    }
    public static function except(array $routes, callable $func)
    {
        return self::route_filtering($routes, $func, false);
    }

    public static function group_page(string|array $pages, callable ...$args)
    {
        if (! $pages) {
            return false;
        }
        if (is_string($pages)) {
            if (str_contains($pages, "/*")) {
                $exp = explode("/*", $pages);
                $parent = $exp[0];
                return self::group_page(ctr_get_routes($parent), ...$args);
            }
            $path = substr($pages, -4) === ".php" ? $pages : $pages . ".php";
            if (! file_exists("_frontend/pages/$path")) {
                //throw new Exception("Group page error: $pages not exist");
            }
            $current = current_page();
            if ($current == $pages) {
                foreach ($args as $func) {
                    try {
                        $func();
                    } catch (Throwable $e) {
                        throw new Exception($e->getMessage());
                    }
                }
            }
        } else if (is_array($pages)) {
            foreach ($pages as $page) {
                self::group_page($page, ...$args);
            }
        } else {
            throw new Exception("Group page error: page should be array/string only");
        }
        return true;
    }

    public static function set(string|array|null $routes, callable ...$args)
    {
        if (! $routes) {
            return false;
        }
        if (is_string($routes)) {
            $current = current_be();
            $path = substr($routes, -4) == ".php" ? $routes : $routes . ".php";
            if (! file_exists("_backend/_app/_routes/$path")) {
                Response::code(notfound_code)
                    ->message("In set route, backend route $routes not found.!")
                    ->send(notfound_code);
            }

            if ($routes === $current) {
                foreach ($args as $func) {
                    try {
                        $func();
                    } catch (Throwable $e) {
                        throw new Exception($e->getMessage());
                    }
                }
            }
        } elseif (is_array($routes)) {
            foreach ($routes as $r) {
                self::set($r, ...$args);
            }
        } else {
            throw new Exception("Routing::set must be string or array only");
        }
        return true;
    }
}
