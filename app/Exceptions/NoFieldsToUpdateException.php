<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class NoFieldsToUpdateException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No fields to update');
    }
}
