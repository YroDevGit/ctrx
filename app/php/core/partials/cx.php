<?php

if(! function_exists("assets")){
    function assets(string $assets){
        include "views/assets/". $assets;
    }
}
