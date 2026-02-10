<?php

//Middleware: auth
use Classes\Response;
use Classes\Ctrx;

$loggedIn = Ctrx::is_logged_in();

if(! $loggedIn){
    Response::code(401)->message("Unauthorized access")->send(500);
}

