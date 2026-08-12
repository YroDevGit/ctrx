<?php
if(! function_exists("ctrx_fe_configuration")){
    function ctrx_fe_configuration(string $key = "*")
    {
        $view_config = $GLOBALS['ctrx_views_conf_a_vars'] ?? [];

        if ($key == "*") {
            return $view_config;
        }

        if (! $key) return null;

        return isset($view_config[$key]) ? $view_config[$key] : null;
    }
}