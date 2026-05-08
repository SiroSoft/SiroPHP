<?php

declare(strict_types=1);

namespace App\Controllers;

use Siro\Core\Request;
use Siro\Core\Response;

/**
 * Home/welcome controller.
 *
 * Serves the root endpoint with API framework information.
 *
 * @package App\Controllers
 */

final class HomeController
{
    public function index(Request $request): Response
    {
        return Response::success([
            'name' => 'Siro API Framework',
            'version' => '0.16.0',
            'php' => PHP_VERSION,
            'environment' => $_ENV['APP_ENV'] ?? 'production',
            'features' => [
                'lightweight-router',
                'pdo-database',
                'env-loader',
                'middleware',
                'json-only-response',
            ],
        ], 'Siro API Framework is running');
    }
}
