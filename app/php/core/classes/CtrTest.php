<?php

namespace Classes;

use Classes\Tyrux;

/**
 * This is CodeTazeR own PHP autmation testing.
 * Created on January 9 2026
 * by: Tyrone Liment Malocon
 * yroez
 * CodeYro
 */

class CtrTest
{
    public static $title = "";
    protected $route = "";
    protected $data = [];
    protected $headers = [];
    protected $method = "POST";
    protected $rcode = null;
    protected $statusCode = null;
    protected $hasData = null;
    protected $rdata = null;
    protected $rerrors = null;
    protected $key = null;
    protected $adata = null;
    protected $keyValue = null;
    protected $msg = [];

    public function __construct($route, $data, $headers, $method)
    {
        include "_frontend/app/php/loadenv.php";
        $this->route = $route;
        $this->data = $data;
        $this->headers = $headers;
        $this->method = $method;
    }

    public static function write(string $title, callable $func)
    {
        self::$title = $title;
        $func();
    }

    public static function execute_post(string $route, array $data = [], array $headers = []) {
        return self::execute($route, $data, $headers, "POST");
    }

    public static function execute_get(string $route, array $data = [], array $headers = []) {
        return self::execute($route, $data, $headers, "GET");
    }

    public static function execute_put(string $route, array $data = [], array $headers = []) {
        return self::execute($route, $data, $headers, "PUT");
    }

    public static function execute_delete(string $route, array $data = [], array $headers = []) {
        return self::execute($route, $data, $headers, "DELETE");
    }

    public static function execute(string $route, array $data = [], array $headers = [], string $method)
    {
        return new self($route, $data, $headers, $method);
    }

    public function showResult(){
        $data = $this->adata;
        $title = self::$title;
        $results = $this->msg;
        $res = implode("\n", $results);
        echo "===================================================================\n";
        echo "TEST: $title\n\n";
        echo "Results:\n";
        echo $res."\n\n";
        echo "Actual data: ". json_encode($data)."\n";
        echo "===================================================================\n\n";
    }

    public function expectedCode(int $code) {
        $this->rcode = $code;
        return $this;
    }

    public function expectedKey($key, $keyvalue = null) {
        $this->key = $key;
        if($keyvalue){
            $this->keyValue = $keyvalue;
        }
        return $this;
    }

    public function expectedDataHas($key) {
        $this->hasData = $key;
        return $this;
    }

    public function expectedStatusCode(int $code) {
        $this->statusCode = $code;
        return $this;
    }

    public function run()
    {
        $result = Tyrux::request($this->method, [
            "url" => getenv("rootpath")."?be=". $this->route,
            "data" => $this->data,
            "headers" => $this->headers,
        ]);

        $status = Tyrux::statusCode();

        $this->adata = $result;

        if($this->key){
            $k = $this->key;
            if(! isset($result[$k])){
                $this->msg[] ="❌ Key [$k] not exist in the result.!";
            }
            if($this->keyValue){
                $kv = $this->keyValue;
                if($kv == $result[$k]){
                    $this->msg[] = "✔️ Expected Key [$k] and Value [$kv] exist.";
                }else{
                    $this->msg[] = "❌ Expected Key [$k] is present but expected value [$kv] doesn't reflected.!";
                }
            }
        }
        if($this->hasData){
            //
        }
        if($this->statusCode){
            $code = $this->statusCode;
            if($status == $code){
                $this->msg[] = "✔️ Status code has value of [$status] as expected.";
            }else{
                $this->msg[] = "❌ Status code has value of [$status], expected value: [$code]";
            }
        }
        if($this->rcode){
            $code = $this->rcode;
            if(! isset($result['code'])){
                $this->msg[] = "❌ Key 'code' not found in the results.!";
            }
            if($this->rcode == $result['code']){
                
                $this->msg[] = "✔️ Key 'code' has value of [$code] as expected";
            }else{
                $rescode = $result['code'];
                $this->msg[] = "❌ Key 'code' has value of [$rescode], expected code is [$code]";
            }
        }
        $this->showResult();
    }
}
