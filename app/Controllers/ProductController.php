<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\{Product};
use Siro\Core\Request;
use Siro\Core\Response;

final class ProductController
{
    public function index(Request $request): Response
    {
        $perPage = $request->queryInt('per_page', 20);
        $page = $request->queryInt('page', 1);

        $result = Product::query()
            ->orderBy('id', 'DESC')
            ->paginate($perPage, $page);

        return Response::paginated($result['data'], $result['meta'], 'products list fetched');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        $item = Product::find($id);
        if ($item === null) {
            return Response::error('Product not found', 404);
        }

        return Response::success($item->toArray(), 'Product fetched');
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|min:3|max:120',
        ]);

        $item = Product::create([
            'name' => $validated['name'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return Response::created($item->toArray(), 'Product created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        $item = Product::find($id);
        if ($item === null) {
            return Response::error('Product not found', 404);
        }

        $validated = $request->validate([
            'name' => 'min:3|max:120',
        ]);

        $item->update($validated);
        return Response::success($item->toArray(), 'Product updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        $item = Product::find($id);
        if ($item === null) {
            return Response::error('Product not found', 404);
        }

        $item->delete();
        return Response::success(null, 'Product deleted');
    }
}
