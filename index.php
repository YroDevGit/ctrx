<?php

/**
 * CTRX framework by CodeYRO
 * This framework made by the filipino dev (Technology made in the philippines)
 * Year created: 2025
 */

/**
 * Setup PHP path: 
 * curl -L -o "%USERPROFILE%\Downloads\install-php.bat" https://raw.githubusercontent.com/YroDevGit/important/refs/heads/main/php-varfile.bat
 */

/**
 * For deployment please attach or generate htaccess for routing
 * command: php ctrx generate:htaccess
 */


/**
 * Load all auto-load files
 * Load and generate env variables
 */
require_once 'vendor/autoload.php';
include_once "app/php/core/partials/envloader.php";

/**
 * CTRX / CodeTazer Dev Server Router
 * Made by CodeYRO
 */

/**
 * URL parsing
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

/**
 * Load javascript without extensions (in javascript import)
 * This load javascript from frontend (js) folder (views/js)
 */
if (str_starts_with($uri, "\\views\js\\") || str_starts_with($uri, "/views/js/")) {
    if (! str_ends_with($uri, ".js") && ! str_ends_with($uri, ".css")) {
        $uri = $uri . ".js";
        $uri = trim($uri, "/");
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $uri);
        finfo_close($finfo);
        header('Content-Type: application/javascript');
        readfile($uri);
        exit;
    }
}

/**
 * Load javascript without extensions (in javascript import)
 * This load javascript from frontend (js) folder (views/code/*)
 */
if (str_starts_with($uri, "\\views\code\src\\") || str_starts_with($uri, "/views/code/src/") || str_starts_with($uri, "/views/code/script/")) {
    if (! str_ends_with($uri, ".js") && ! str_ends_with($uri, ".css")) {
        $uri = $uri . ".js";
        $uri = trim($uri, "/");
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $uri);
        finfo_close($finfo);
        header('Content-Type: application/javascript');
        readfile($uri);
        exit;
    }
}

/**
 * This is Ctr storage reader
 * where you can read public and filter private files
 */
if (str_starts_with($uri, "/ctrstorage/")) {
    $ctrstorage = substr($uri, strlen('/ctrstorage/'));
    $filePath = 'views/core/partials/storage/' . $ctrstorage;

    if (!\Classes\Ctrx::file_exists_strict($filePath)) {
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

        if (\Classes\Ctrx::file_exists_strict($forbidden)) {
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
    if (str_contains($file, "views/code/src/") || str_contains($file, "views/js/")) return false;
    if (in_array($ext, $staticExtensions)) {
        return false;
    }

    /**
     * Block direct PHP access
     */
    if ($ext === 'php') {
        http_response_code(403);

        $forbidden = __DIR__ . '/views/core/errors/forbidden.php';

        if (\Classes\Ctrx::file_exists_strict($forbidden)) {
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
