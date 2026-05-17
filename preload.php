<?php

declare(strict_types=1);

/**
 * Opcache Preloading Script
 *
 * This file is loaded via opcache.preload in php.ini or opcache.preload_file via Apache/Nginx config.
 *
 * Preloading provides ~10-20% performance improvement by compiling all framework
 * classes into shared memory (opcache) at server startup.
 *
 * Usage:
 *   1. Add to php.ini: opcache.preload=/path/to/SiroPHP/preload.php
 *   2. Or in Apache: php_admin_value opcache.preload /path/to/SiroPHP/preload.php
 *   3. Or in Nginx: fastcgi_param PHP_VALUE "opcache.preload=/path/to/SiroPHP/preload.php"
 *
 * After changes, restart PHP-FPM/Apache/Nginx to apply.
 */

$basePath = __DIR__;
$corePath = $basePath . '/vendor/sirosoft/core';

if (!defined('SIRO_BASE_PATH')) {
    define('SIRO_BASE_PATH', $basePath);
}

$files = [
    $corePath . '/App.php',
    $corePath . '/Router.php',
    $corePath . '/RouteMatcher.php',
    $corePath . '/Request.php',
    $corePath . '/Response.php',
    $corePath . '/Config.php',
    $corePath . '/Env.php',
    $corePath . '/Logger.php',
    $corePath . '/Logger/LoggerInstance.php',

    $corePath . '/Database.php',
    $corePath . '/Schema.php',
    $corePath . '/DB/QueryBuilder.php',
    $corePath . '/DB/SqlCompiler.php',
    $corePath . '/DB/ModelQueryBuilder.php',
    $corePath . '/DB/Blueprint.php',
    $corePath . '/DB/Column.php',
    $corePath . '/DB/DatabaseInstance.php',
    $corePath . '/DB/EagerLoader.php',

    $corePath . '/Auth/JWT.php',
    $corePath . '/Auth/AuthGuard.php',
    $corePath . '/Auth/ApiKey.php',
    $corePath . '/Auth/Idempotency.php',

    $corePath . '/Model.php',
    $corePath . '/Cache.php',
    $corePath . '/Cache/CacheInstance.php',
    $corePath . '/Storage.php',

    $corePath . '/Middleware/AuthMiddleware.php',
    $corePath . '/Middleware/ThrottleMiddleware.php',
    $corePath . '/Middleware/CorsMiddleware.php',
    $corePath . '/Middleware/JsonMiddleware.php',
    $corePath . '/Middleware/ApiKeyMiddleware.php',
    $corePath . '/Middleware/IdempotencyMiddleware.php',

    $corePath . '/Str.php',
    $corePath . '/Collection.php',
    $corePath . '/Hash.php',
    $corePath . '/Encrypter.php',
    $corePath . '/Container.php',
    $corePath . '/Lang.php',
    $corePath . '/Session.php',
    $corePath . '/Queue.php',
    $corePath . '/UploadedFile.php',
    $corePath . '/ValidationException.php',
    $corePath . '/Validator.php',
    $corePath . '/Metrics.php',
    $corePath . '/Mail.php',

    $corePath . '/DB/Relations/HasOne.php',
    $corePath . '/DB/Relations/HasMany.php',
    $corePath . '/DB/Relations/BelongsTo.php',
    $corePath . '/DB/Relations/BelongsToMany.php',

    $corePath . '/Console.php',
    $corePath . '/Commands/CommandSupport.php',
];

foreach ($files as $file) {
    if (is_file($file)) {
        require_once $file;
    }
}

$modelsPath = $basePath . '/app/Models';
if (is_dir($modelsPath)) {
    foreach (glob($modelsPath . '/*.php') as $file) {
        if (is_file($file)) {
            require_once $file;
        }
    }
}

$controllersPath = $basePath . '/app/Controllers';
if (is_dir($controllersPath)) {
    foreach (glob($controllersPath . '/*.php') as $file) {
        if (is_file($file)) {
            require_once $file;
        }
    }
}

echo "SiroPHP preloaded successfully.\n";