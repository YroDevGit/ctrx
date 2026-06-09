<?php
if (! function_exists("read_ctr_file")) {
    function read_ctr_file($file, $mime)
    {
        header("Content-Type: $mime");
        readfile($file);
        exit;
    }
}
