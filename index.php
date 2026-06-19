<?php

require_once 'vendor/autoload.php';
include_once "app/php/core/partials/envloader.php";

/**
 * CTRX / CodeTazer Dev Server Router
 * Made by CodeYRO
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (str_starts_with($uri, "/ctrstorage/")) {
    $ctrstorage = substr($uri, strlen('/ctrstorage/'));
    $filePath = 'views/core/partials/storage/' . $ctrstorage;

    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('File not found');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);    
    
    $ret = extract([
        "path" => $ctrstorage,
        "file_path" => $filePath,
        "mime_type" => $mimeType,
        "dir" => dirname($ctrstorage)
    ]);
    include "app/php/core/partials/filereader.php";
    include "app/config/storage_config.php";
}

/**
 * Normalize root
 */
if ($uri === false) {
    $uri = '/';
}

$file = __DIR__ . $uri;

/**
 * Protected folders
 */
$blocked = [
    '/views/pages/',
    '/views/includes/',
    '/views/core/',
    '/views/app/',
    '/app/_controller/',
    '/app/_routes/',
    '/app/_auto/',
    '/app/php/core/',
];

/**
 * Block protected access
 */
foreach ($blocked as $path) {
    if (strpos($uri, $path) === 0) {
        http_response_code(403);

        $forbidden = __DIR__ . '/views/core/errors/forbidden.php';

        if (file_exists($forbidden)) {
            include $forbidden;
        } else {
            echo "403 Forbidden";
        }

        exit;
    }
}

/**
 * Allowed static asset extensions
 */
$staticExtensions = [
    'js',
    'mjs',
    'css',
    'png',
    'jpg',
    'jpeg',
    'gif',
    'svg',
    'webp',
    'ico',
    'woff',
    'woff2',
    'ttf',
    'eot',
    'map',
    'json',
    'txt',
    'xml',
    'mp4',
    'webm',
    'mp3',
];

/**
 * Serve static files directly
 */

if (
    $uri !== '/'
) {

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if(str_contains($file,"views/code/src/") || str_contains($file, "views/js/"))return false;
    if (in_array($ext, $staticExtensions)) {
        return false;
    }

    /**
     * Block direct PHP access
     */
    if ($ext === 'php') {
        http_response_code(403);

        $forbidden = __DIR__ . '/views/core/errors/forbidden.php';

        if (file_exists($forbidden)) {
            include $forbidden;
        } else {
            echo "403 Forbidden";
        }

        exit;
    }
}

/**
 * Route all requests into CTRX
 */
require 'app/php/core/server.php';