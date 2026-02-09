<?php
if (file_exists(".env")) {
    $env_file = fopen(".env", 'r');

    if ($env_file) {
        while (($line = fgets($env_file)) !== false) {
            $line = trim($line);
            if ($line && strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);

                $key = trim($key);
                if (str_starts_with($key, "#")) {
                    continue;
                }
                $value  = trim($value);
                $cenv = getenv($key);

                if ($cenv && $cenv === $value) {
                    continue;
                }
                putenv("$key=$value");
                $_ENV[$key]     = $value;
            }
        }
        fclose($env_file);
    }
}
if (! defined("ctr_secure_key")) define("ctr_secure_key", "csrf_ctrsk_" . getenv('secure_key'));
if(! function_exists("env")){
    function env(string $key){
        return getenv($key);
    }
}
