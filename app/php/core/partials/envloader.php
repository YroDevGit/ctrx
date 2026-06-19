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
                $cenv = $_ENV[$key] ?? null;

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
if (! defined("ctr_secure_key")) define("ctr_secure_key", "csrf_ctrsk_" . $_ENV['secure_key']);
if (! function_exists("env")) {
    function env(string $key)
    {
        return $_ENV[$key] ?? null;
    }
}

if (! function_exists("variable")) {
    function variable(string $key)
    {
        $view_config = file_get_contents("views/fe_config.json");
        $view_config = json_decode($view_config, true);
        if (isset($view_config['variable'])) {
            $vr = $view_config['variable'];
            return $vr[$key] ?? null;
        }
        return null;
    }
}
