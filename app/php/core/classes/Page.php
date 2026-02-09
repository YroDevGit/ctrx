<?php
namespace Classes;

use Exception;

class Page{

    private static $parent = "";
    private string $route = "";
    private string $group = "";
    private array $arr = [];

    public function __construct(string $var = null, $group = false, array $arr = [])
    {
        if ($group) {
            $this->group = $var;
            $this->arr = $arr;
        }
        else $this->route = $var;
    }

    public static function is(string $route)
    {
        self::checkRoutes($route);
        $key = 'ctrxfe_' . $route;
        $_REQUEST[$key] = ["route" => $route];
        return new self($key);
    }

    public function middleware(string ...$middleware)
    {
        foreach($middleware as $k=>$v){
            $file = append_php($v);
            if(! file_exists("views/app/middleware/$file")){
                throw new Exception("Client: Middleware '$file' not found.!");
            }
        }
        $key = $this->route;
        if ($key) {
            $_REQUEST[$key]["middleware"] = [...$middleware];
            return $this;
        }
        
        foreach($this->arr as $k=>$v){
            $kk = strtolower($k);
            $key = "ctrxfe_".$v;
            $_REQUEST[$key]['middleware'] = [...$middleware];
        }
        return $this;
    }

    static function group(array $routes)
    {
        foreach ($routes as $k => $v) {
            self::checkRoutes($v);
            $key = strtolower($k);
            $key = "ctrxfe_" . $v;
            $_REQUEST[$key] = ["route" => $v];
        }
        $unique = bin2hex(random_bytes(10));
        return new self($unique, true, $routes);
    }

    private static function checkRoutes(string $route){
        $route = append_php($route);
        if(! file_exists("views/pages/".$route)){
            throw new Exception("Client: Page '$route' not a found or not a file.!");
        }
    }

}
?>