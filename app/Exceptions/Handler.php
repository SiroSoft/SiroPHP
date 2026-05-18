<?php

declare(strict_types=1);

namespace App\Exceptions;

use Siro\Core\Env;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\ValidationException;
use Siro\Core\ModelNotFoundException;
use Siro\Core\Logger;

final class Handler
{
    public static function handle(\Throwable $e, Request $request): Response
    {
        Logger::error($e);

        return match (true) {
            $e instanceof ValidationException => $e->toResponse(),
            $e instanceof ModelNotFoundException => Response::error($e->getMessage(), 404),
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
}
