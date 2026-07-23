<?php
use Classes\CtrStorage;
use Classes\Ctrx;
use Classes\Response;
/**
 * This is a middleware for storage file upload
 * upload using CImagePicker
 */
$role = Ctrx::get_user_role(); // Current user role
$allow = ["admin"]; // roles allowed to upload

$dir = get('dir');  // requested directory

// You can add more validation here...

if (in_array($role, $allow)) {
    CtrStorage::ctr_upload_image($dir);
} else {
    Response::code(unauthorized_code)->message("Unauthorized access")->send();
}
