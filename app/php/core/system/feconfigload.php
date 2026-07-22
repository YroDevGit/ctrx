<?php
if(! function_exists("ctrx_fe_configuration")){
    function ctrx_fe_configuration(string $key = "*")
    {
        $view_config = file_get_contents("views/fe_config.json");
        $view_config = json_decode($view_config, true);

        if ($key == "*") {
            return $view_config;
        }

        if (! $key) return null;

        return isset($view_config[$key]) ? $view_config[$key] : null;
    }
}