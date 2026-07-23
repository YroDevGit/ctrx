<?php
use Classes\CtrStorage;
/**
 * Middleware for Storage file reader
 * Available variables: $path, $file_path, $mime_type, $dir
 * $path = file path: subdirectory/filename
 * $file_path = full path of the file
 * $mime_type = mime type
 * $dir = a subfolder inside ctr storage
 */

if ($dir == "public") {
    CtrStorage::ctr_read_file($file_path, $mime_type);
}