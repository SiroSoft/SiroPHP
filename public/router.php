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
// 2. All other requests → normal application bootstrap
// ──────────────────────────────────────────────
require __DIR__ . '/index.php';
