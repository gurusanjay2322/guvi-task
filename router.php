<?php
// router.php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// 1. Serve static files directly
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// 2. PHP files in /php folder
if (preg_match('/\.php$/', $path)) {
    $phpFile = __DIR__ . '/php' . $path; // <-- fix
    if (file_exists($phpFile)) {
        include $phpFile;
        return true;
    } else {
        http_response_code(404);
        echo "404 Not Found (PHP file)";
        return true;
    }
}

// 3. Fallback: serve index.html
include __DIR__ . '/index.html';
