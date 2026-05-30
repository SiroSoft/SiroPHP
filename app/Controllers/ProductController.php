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

    // Rate limited: 60 requests per minute. Non-admin users see only their products.
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

    // Admin only.
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

    // Admin only.
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

    // Admin only.
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
