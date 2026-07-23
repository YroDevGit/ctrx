<?php
use Classes\CtrStorage;
use Classes\Ctrx;
use Classes\Response;
/**
 * This is a middleware for storage file delete
 * delete using CImagePicker
 */
$role = Ctrx::get_user_role(); // Current user role
$allow = ["admin"]; // roles allowed to delete

$dir = get('dir'); // requested directory

// You can more validations here..

if(in_array($role, $allow)){
    CtrStorage::ctr_remove_image($dir);
}else{
    Response::code(unauthorized_code)->message("Unauthorized access")->send();
}
