<?php

declare(strict_types=1);

namespace App\Controllers;

use Siro\Core\Request;
use Siro\Core\Response;

final class TagController
{
    public function index(Request $request): Response
    {
        return Response::success([], 'Tags list');
    }

    public function show(Request $request): Response
    {
        return Response::error('Tag not found', 404);
    }

    public function store(Request $request): Response
    {
        return Response::created(['id' => 0], 'Tag created');
    }

    public function update(Request $request): Response
    {
        return Response::success(null, 'Tag updated');
    }

    public function delete(Request $request): Response
    {
        return Response::success(null, 'Tag deleted');
    }
}
