<?php

declare(strict_types=1);

/**
 * Router script for PHP built-in development server.
 *
 * Handles CORS preflight (OPTIONS) requests early — before
 * booting the framework — so the browser gets CORS headers
 * immediately without framework overhead.
 *
 * PHP built-in server behavior with this router script:
 *   php -S localhost:8000 -t public public/router.php
 *
 * - Static files that exist in public/ → served directly by PHP
 * - Virtual paths (e.g. /api/v1/auth/login) → routed here → index.php
 * - OPTIONS preflight → handled here → 204 with CORS headers → exit
 */

// ──────────────────────────────────────────────
// 0. Load .env early for CORS (framework not booted yet)
// ──────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if ($key !== '') {
                putenv($key . '=' . $value);
            }
        }
    }
}

// ──────────────────────────────────────────────
// 1. OPTIONS preflight — respond without framework boot
// ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $allowedOrigins = getenv('CORS_ALLOWED_ORIGINS') ?: '*';
    $allowedMethods = getenv('CORS_ALLOWED_METHODS') ?: 'GET,POST,PUT,DELETE,OPTIONS,PATCH';
    $allowedHeaders = getenv('CORS_ALLOWED_HEADERS') ?: 'Content-Type,Authorization,X-Requested-With,X-CSRF-TOKEN';

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($allowedOrigins === '*') {
        header('Access-Control-Allow-Origin: *');
    } elseif ($origin !== '') {
        $origins = array_map('trim', explode(',', $allowedOrigins));
        if (in_array($origin, $origins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
    }

    header('Access-Control-Allow-Methods: ' . $allowedMethods);
    header('Access-Control-Allow-Headers: ' . $allowedHeaders);
    header('Access-Control-Expose-Headers: X-RateLimit-Limit,X-RateLimit-Remaining,X-RateLimit-Reset,X-Siro-Trace-Id');
    header('Access-Control-Max-Age: 86400');
    header('Vary: Origin');

    http_response_code(204);
    exit(0);
}

// ──────────────────────────────────────────────
// 2. Serve uploaded files from storage/public/
// ──────────────────────────────────────────────
$rawUri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
$requestUri = parse_url($rawUri, PHP_URL_PATH);
if (is_string($requestUri) && str_starts_with($requestUri, '/storage/')) {
    // Allow cross-origin access from configured origins only
    $origin = is_string($_SERVER['HTTP_ORIGIN'] ?? null) ? $_SERVER['HTTP_ORIGIN'] : '';
    $allowedOrigins = array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: ''));
    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }

    $relativePath = substr($requestUri, 9); // Remove '/storage/'
    $storageFile = __DIR__ . '/../storage/public/' . $relativePath;
    $realFile = realpath($storageFile);
    $storagePublic = realpath(__DIR__ . '/../storage/public');
    if ($realFile !== false && $storagePublic !== false && str_starts_with($realFile, $storagePublic) && is_file($realFile)) {
        $mime = (function_exists('mime_content_type') ? mime_content_type($realFile) : 'application/octet-stream') ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($realFile));
        header('Cache-Control: public, max-age=31536000');
        readfile($realFile);
        exit(0);
    }
}

// ──────────────────────────────────────────────
// 3. All other requests → normal application bootstrap
// ──────────────────────────────────────────────
require __DIR__ . '/index.php';
