<?php

if (! function_exists("cbe_ctrx_endpoint")) {
    function cbe_ctrx_endpoint()
    {
        try {
            $param = ctrx_param;
            if (str_starts_with($param, "api/") || str_starts_with($param, "ctrx.yro.ctrstorage.images/")) {
                return "BE";
            }
            return "FE";
        } catch (Throwable $e) {
            return "BE";
        }
    }
}