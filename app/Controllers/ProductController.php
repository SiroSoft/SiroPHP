<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\ProductResource;
use App\Services\ProductService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service)
    {
    }

    public function index(Request $request): Response
    {
        $perPage = min($request->queryInt('per_page', 20), 100);
        $page = max($request->queryInt('page', 1), 1);

        $result = $this->service->getAll($request->all(), $page, $perPage);
        /** @var array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}} $result */

        return $this->paginated(
            ProductResource::collection($result['data']),
            $result['meta'],
            'Products list',
        );
    }

    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        $item = $this->service->getById($id);
        /** @var array<string, mixed>|null $item */
        if ($item === null) {
            return $this->error('Product not found', 404);
        }

        return $this->success(ProductResource::make($item), 'Product fetched');
    }

    public function store(Request $request): Response
    {
        $validated = $this->validate([
            'name' => 'required|min:1|max:255',
            'description' => 'max:65535',
            'price' => 'numeric|min:0',
            'stock' => 'integer',
            'category' => 'max:100',
            'status' => 'max:20',
        ]);

        $item = $this->service->create($validated);
        /** @var array<string, mixed> $item */
        return $this->created(ProductResource::make($item), 'Product created');
    }

    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        $validated = $this->validate([
            'name' => 'min:1|max:255',
            'description' => 'max:65535',
            'price' => 'numeric',
            'stock' => 'integer',
            'category' => 'max:100',
            'status' => 'max:20',
        ]);

        $item = $this->service->update($id, $validated);
        /** @var array<string, mixed>|null $item */
        if ($item === null) {
            return $this->error('Product not found', 404);
        }

        return $this->success(ProductResource::make($item), 'Product updated');
    }

    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Product not found', 404);
    }
}
