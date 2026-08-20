<?php
use Classes\CtrStorage;
use Classes\Ctrx;
use Classes\Response;
/**
 * This is a middleware for fetching image list
 * used in CImagePicker
 */
$dir = get("dir"); //requested directory
$role = Ctrx::get_user_role(); // Current user role
$access = [];

//Filter role access
$filter = [
    "admin" => ["public"],
];

//Role and directory validation
if($role){
    $access = $filter[$role] ?? $access;
}
if(! in_array($dir, $access)){
    Response::code(unauthorized_code)->message("Unauthorized access")->send();
}

// You can add more validations here...
CtrStorage::get_images($dir);