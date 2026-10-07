<?php

declare(strict_types=1);

namespace App\Exceptions;

use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\ValidationException;
use Siro\Core\ModelNotFoundException;
use Siro\Core\DB\DatabaseConnectionException;
use Siro\Core\Logger;
use App\Exceptions\DuplicateEmailException;
use App\Exceptions\NoFieldsToUpdateException;

final class Handler
{
    public static function handle(\Throwable $e, Request $request): Response
    {
        Logger::error($e);

        return match (true) {
            $e instanceof ValidationException => $e->toResponse(),
            $e instanceof ModelNotFoundException => Response::error('Resource not found', 404),
            $e instanceof DatabaseConnectionException => self::dbError(),
            $e instanceof DuplicateEmailException => Response::error('Validation failed', 409),
            $e instanceof NoFieldsToUpdateException => Response::error('No fields to update', 400),
            default => self::defaultError(),
        };
    }

    private static function defaultError(): Response
    {
        return Response::error('Internal Server Error', 500);
    }

    private static function dbError(): Response
    {
        return Response::error('Service temporarily unavailable', 503);
    }
}
