<?php
use Classes\CtrStorage;
/**
 * This is a middleware for storage file delete
 * delete using CImagePicker
 */

$roles = ["admin"]; // user roles that can delete image
$dir = get('dir'); // file directory inside storage


CtrStorage::ctr_remove_image(roles:$roles);