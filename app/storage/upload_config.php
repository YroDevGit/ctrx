<?php
use Classes\CtrStorage;
use Classes\Ctrx;
/**
 * This is a middleware for storage file upload
 * upload using CImagePicker
 */

$roles = ["admin"]; // user roles that can upload image
$dir = get('dir');  // file directory inside storage


CtrStorage::ctr_upload_image(roles:$roles);