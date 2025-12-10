<?php

// index.php - Main application entry point

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Include helpers
require_once __DIR__ . '/../src/Helpers/functions.php';

// Create and boot application
$app = new \App\Core\Application(__DIR__ . '/..');
$app->boot();

// Handle request
$app->handle();
