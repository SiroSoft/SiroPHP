<?php

declare(strict_types=1);

namespace App\Controllers;

use Siro\Core\Request;
use Siro\Core\Response;

final class TestcheckcontrollerController
{
    public function index(Request ): Response
    {
        return Response::success([], 'TestcheckcontrollerController index');
    }
}
