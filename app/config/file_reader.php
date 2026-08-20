<?php
use Classes\CtrStorage;
use Classes\Ctrx;

/**
 * Middleware for Storage file reader
 * Available variables: $path, $file_path, $mime_type, $dir
 * $path = file path: subdirectory/filename
 * $file_path = full path of the file
 * $mime_type = mime type
 * $dir = a subfolder inside ctr storage
 */

$role = Ctrx::get_user_role();
$access = ["public"]; // Default public access

//Authenticated user role access filter
$filter = [
    "admin" => ["public"],
];
//get directory access by role
if($role){
    $access = $filter[$role] ?? $access;
}

//Check if request directory exists
if (in_directory($access, $dir)) {
    //Read file
    CtrStorage::ctr_read_file($file_path, $mime_type);
}