<?php
/**
 * This is a middleware for upload public
 * upload using CImagePicker
 */

use Classes\CtrStorage;
use Classes\Ctrx;

$roles = ["admin"]; // user roles that can upload image

CtrStorage::ctrUploadImage(roles:$roles);