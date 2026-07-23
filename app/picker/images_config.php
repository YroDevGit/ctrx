<?php
use Classes\CtrStorage;
use Classes\Ctrx;
use Classes\Response;
/**
 * This is a middleware for fetching image list
 * used in CImagePicker
 */
$role = Ctrx::get_user_role(); // Current user role
$allow = ["admin"]; // roles allowed to fetch

$dir = get("dir"); //requested directory

// You can add more validations here...

if(in_array($role, $allow)){
    CtrStorage::get_images($dir);
}else{
    Response::code(unauthorized_code)->message("Unauthorized access")->send();
}
