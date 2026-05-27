<?php

/**
 * Yro Blocker
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$blocked = [
    '/views/pages/',
    '/views/includes',
    '/views/core',
    '/views/app',
    '/app/_controller',
    '/app/_routes',
    '/app/_auto',
    '/app/php/core'
];

foreach ($blocked as $path) {
    if (strpos($uri, $path) === 0) {
        http_response_code(403);
        include "views/core/errors/forbidden.php";
    }
}

$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

require 'index.php';
