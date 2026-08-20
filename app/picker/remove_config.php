<?php
use Classes\CtrStorage;
use Classes\Ctrx;
use Classes\Response;
/**
 * This is a middleware for storage file delete
 * delete using CImagePicker
 */
$dir = get('dir'); // requested directory
$role = Ctrx::get_user_role(); // Current user role
$access = []; // roles allowed to delete

//Filter role access
$filter = [
    "admin" => ["public"],
];

//get directory access by role 
if($role){
    $access = $filter[$role] ?? $access;
}
if(! in_array($dir, $access)){
    Response::code(unauthorized_code)->message("Unauthorized access")->send();
}

// You can more validations here..
CtrStorage::ctr_remove_image($dir);