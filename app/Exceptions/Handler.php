<?php

declare(strict_types=1);

namespace App\Exceptions;

use Siro\Core\Env;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\ValidationException;
use Siro\Core\ModelNotFoundException;
use Siro\Core\DB\DatabaseConnectionException;
use Siro\Core\Logger;

final class Handler
{
    public static function handle(\Throwable $e, Request $request): Response
    {
        Logger::error($e);

        return match (true) {
            $e instanceof ValidationException => $e->toResponse(),
            $e instanceof ModelNotFoundException => Response::error($e->getMessage(), 404),
            $e instanceof DatabaseConnectionException => self::dbError($e),
            default => self::defaultError($e),
        };
    }

    private static function defaultError(\Throwable $e): Response
    {
        $debug = Env::bool('APP_DEBUG', false);

        $message = $debug ? $e->getMessage() : 'Internal Server Error';
        $data = $debug ? ['trace' => $e->getTraceAsString()] : [];

        return Response::error($message, 500, $data);
    }

    private static function dbError(DatabaseConnectionException $e): Response
    {
        $debug = Env::bool('APP_DEBUG', false);

        return Response::error(
            $debug ? $e->getMessage() : 'Database connection failed. Please check your database configuration.',
            500,
            $debug ? ['driver' => $e->getDriver(), 'host' => $e->getDbHost()] : []
        );
    }
}
