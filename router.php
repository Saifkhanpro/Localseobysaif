<?php
// router.php - Used for PHP built-in web server
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico|webp|svg)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
}

// Request path without query string
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// If the file exists and is a php file (like install.php), include it directly
if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path) && substr($path, -4) === '.php') {
    require_once __DIR__ . $path;
    return true;
}

// Otherwise route through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/index.php';
