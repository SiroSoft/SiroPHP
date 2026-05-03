<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Register tests namespace for PHPUnit test cases
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\Tests\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', '1');
