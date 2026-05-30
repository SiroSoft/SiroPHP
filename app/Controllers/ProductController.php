<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\ProductResource;
use App\Role;
use App\Services\ProductService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service)
    {
    }

    /**
     * List all products with filtering, sorting, and pagination.
     *
     * Supports search (name), category, status, price_min/max, sort (id/name/price/stock/created_at), order (asc/desc).
     * Rate limited: 60 requests per minute.
     *
     * GET /api/products?page=1&per_page=20&search=...&category=...&price_min=0&price_max=1000&sort=price&order=asc
     *
     * @param Request $request Incoming HTTP request with optional query params
     * @return Response Paginated list of products
     */
    public function index(Request $request): Response
    {
        $perPage = min($request->queryInt('per_page', 20), 100);
        $page = max($request->queryInt('page', 1), 1);

        $params = $request->all();
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) {
            unset($params['user_id']);
        }
        $result = $this->service->getAll($params, $page, $perPage);

        return $this->paginated(
            ProductResource::collection($result['data']),
            $result['meta'],
            'Products list',
        );
    }

    /**
     * Get a single product by ID.
     *
     * GET /api/products/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Product detail (200) or error (404/422)
     */
    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        $item = $this->service->getById($id);
        if ($item === null) {
            return $this->error('Product not found', 404);
        }

        return $this->success(ProductResource::make($item), 'Product fetched');
    }

    /**
     * Create a new product.
     *
     * Admin only. Accepts name, description, price, stock, category, status, cover_image, short_description.
     * Optionally resolves category_id to category name.
     *
     * POST /api/products
     * Body: { name: string, price: float, stock: int, description?: string, is_active?: bool, category_name?: string, category_id?: int, cover_image?: string, short_description?: string }
     *
     * @param Request $request Incoming HTTP request with product data
     * @return Response Created product (201) or error (403/422)
     */
    public function store(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $validated = $this->validate([
            'name' => 'required|min:1|max:255',
            'description' => 'max:65535',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'max:100',
            'status' => 'max:20',
            'cover_image' => 'max:2048',
            'short_description' => 'max:500',
        ]);

        $rawBody = $request->all();
        if (isset($rawBody['is_active'])) {
            $validated['status'] = $rawBody['is_active'] ? 'active' : 'inactive';
        }
        if (isset($rawBody['category_name'])) {
            $validated['category'] = $rawBody['category_name'];
        }

        if (isset($rawBody['category_id'])) {
            try {
                $cat = \Siro\Core\Database::first("SELECT name FROM categories WHERE id = ? LIMIT 1", [(int) $rawBody['category_id']]);
                if ($cat) $validated['category'] = $cat['name'];
            } catch (\Throwable) {}
        }

        $currentUser = $request->user();
        $currentUserId = is_array($currentUser) && isset($currentUser['id']) ? (int) $currentUser['id'] : 0;
        $validated['user_id'] = $currentUserId;

        $item = $this->service->create($validated);
        return $this->created(ProductResource::make($item), 'Product created');
    }

    /**
     * Update an existing product.
     *
     * Admin only. Partial updates supported.
     *
     * PUT /api/products/{id}
     * Body: { name?: string, price?: float, stock?: int, description?: string, is_active?: bool, category_name?: string, category_id?: int, cover_image?: string, short_description?: string }
     *
     * @param Request $request Incoming HTTP request with product updates
     * @return Response Updated product (200) or error (403/404/422)
     */
    public function update(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        $validated = $this->validate([
            'name' => 'min:1|max:255',
            'description' => 'max:65535',
            'price' => 'numeric|min:0',
            'stock' => 'integer|min:0',
            'category' => 'max:100',
            'status' => 'max:20',
            'cover_image' => 'max:2048',
            'short_description' => 'max:500',
        ]);

        $rawBody = $request->all();
        if (isset($rawBody['is_active'])) {
            $validated['status'] = $rawBody['is_active'] ? 'active' : 'inactive';
        }
        if (isset($rawBody['category_name'])) {
            $validated['category'] = $rawBody['category_name'];
        }
        if (isset($rawBody['category_id'])) {
            try {
                $cat = \Siro\Core\Database::first("SELECT name FROM categories WHERE id = ? LIMIT 1", [(int) $rawBody['category_id']]);
                if ($cat) $validated['category'] = $cat['name'];
            } catch (\Throwable) {}
        }

        $item = $this->service->update($id, $validated);
        if ($item === null) {
            return $this->error('Product not found', 404);
        }

        return $this->success(ProductResource::make($item), 'Product updated');
    }

    /**
     * Delete a product by ID.
     *
     * Admin only.
     *
     * DELETE /api/products/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Empty (204) or error (403/404/422)
     */
    public function delete(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Product not found', 404);
    }

    private function requireAdmin(Request $request): ?Response
    {
        $user = $request->user();
        $role = is_array($user) && isset($user['role']) && is_string($user['role']) ? $user['role'] : Role::USER;
        if ($role !== Role::ADMIN) {
            return Response::error('Forbidden', 403);
        }
        return null;
    }
}
