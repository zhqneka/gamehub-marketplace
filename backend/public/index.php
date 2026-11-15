<?php

declare(strict_types=1);

// Router script for the PHP built-in server. Existing files are served as-is.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

App\Application::boot(dirname(__DIR__))->handle();
