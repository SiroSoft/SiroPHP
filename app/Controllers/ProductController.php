<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProductService;
use App\Resources\ProductResource;
use Siro\Core\Request;
use Siro\Core\Response;

final class ProductController
{
    public function __construct(private readonly ProductService $service)
    {
    }

    public function index(Request $request): Response
    {
        $perPage = min($request->queryInt('per_page', 20), 100);
        $page = max($request->queryInt('page', 1), 1);

        $result = $this->service->getAll($request->all(), $page, $perPage);

        return Response::paginated(
            ProductResource::collection($result['data']),
            $result['meta'],
            'Products list',
        );
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        $item = $this->service->getById($id);
        if ($item === null) {
            return Response::error('Product not found', 404);
        }

        return Response::success(ProductResource::make($item), 'Product fetched');
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|min:1|max:255',
            'description' => 'max:5000000',
            'price' => 'numeric|min:0',
            'stock' => 'integer',
            'category' => 'max:100',
            'status' => 'max:20',
        ]);

        $item = $this->service->create($validated);
        return Response::created(ProductResource::make($item), 'Product created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        $validated = $request->validate([
            'name' => 'min:1|max:255',
            'description' => 'max:65535',
            'price' => 'numeric',
            'stock' => 'integer',
            'category' => 'max:100',
            'status' => 'max:20',
        ]);

        $item = $this->service->update($id, $validated);
        if ($item === null) {
            return Response::error('Product not found', 404);
        }

        return Response::success(ProductResource::make($item), 'Product updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        return $this->service->delete($id)
            ? Response::noContent()
            : Response::error('Product not found', 404);
    }
}
