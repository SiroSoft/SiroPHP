<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\CategoryResource;
use App\Role;
use App\Services\CategoryService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service)
    {
    }

    /**
     * List all categories with pagination.
     *
     * Rate limited: 60 requests per minute.
     *
     * GET /api/categories?page=1&per_page=20
     *
     * @param Request $request Incoming HTTP request with optional query params
     * @return Response Paginated list of categories
     */
    public function index(Request $request): Response
    {
        $result = $this->service->getAll(page: max(1, $request->queryInt('page', 1)), perPage: min(100, max(1, $request->queryInt('per_page', 20))));
        $data = [];
        foreach ($result['data'] as $item) {
            $data[] = $item->toArray();
        }
        return $this->paginated(CategoryResource::collection($data), $result['meta'], 'Category list');
    }

    /**
     * Get a single category by ID.
     *
     * GET /api/categories/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Category detail (200) or error (404)
     */
    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->getById($id);
        if ($item === null) return $this->error('Category not found', 404);
        return $this->success(CategoryResource::make($item), 'Category detail');
    }

    /**
     * Create a new category.
     *
     * Admin only. Accepts name, is_active, color, description, sort_order, parent_id.
     *
     * POST /api/categories
     * Body: { name: string, is_active?: bool, color?: string, description?: string, sort_order?: int, parent_id?: int }
     *
     * @param Request $request Incoming HTTP request with validated category data
     * @return Response Created category (201) or error (403/422)
     */
    public function store(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $validated = $this->validate(['name' => 'required|min:2|max:100']);
        $rawBody = $request->all();
        if (isset($rawBody['is_active'])) {
            $validated['is_active'] = $rawBody['is_active'] ? 1 : 0;
        }
        if (isset($rawBody['color'])) {
            $validated['color'] = $rawBody['color'];
        }
        if (isset($rawBody['description'])) {
            $validated['description'] = $rawBody['description'];
        }
        if (isset($rawBody['sort_order'])) {
            $validated['sort_order'] = is_numeric($rawBody['sort_order']) ? (int) $rawBody['sort_order'] : 0;
        }
        if (isset($rawBody['parent_id'])) {
            $validated['parent_id'] = is_numeric($rawBody['parent_id']) ? (int) $rawBody['parent_id'] : null;
        }
        $item = $this->service->create($validated);
        return $this->created(CategoryResource::make($item), 'Category created');
    }

    /**
     * Update an existing category.
     *
     * Admin only. Partial updates supported.
     *
     * PUT /api/categories/{id}
     * Body: { name?: string, is_active?: bool, color?: string, description?: string, sort_order?: int, parent_id?: int }
     *
     * @param Request $request Incoming HTTP request with category updates
     * @return Response Updated category (200) or error (403/404/422)
     */
    public function update(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $validated = $this->validate([
            'name' => 'min:2|max:100',
        ]);
        $rawBody = $request->all();
        if (isset($rawBody['is_active'])) {
            $validated['is_active'] = $rawBody['is_active'] ? 1 : 0;
        }
        if (isset($rawBody['color'])) {
            $validated['color'] = $rawBody['color'];
        }
        if (isset($rawBody['description'])) {
            $validated['description'] = $rawBody['description'];
        }
        if (isset($rawBody['sort_order'])) {
            $validated['sort_order'] = is_numeric($rawBody['sort_order']) ? (int) $rawBody['sort_order'] : 0;
        }
        if (isset($rawBody['parent_id'])) {
            $validated['parent_id'] = is_numeric($rawBody['parent_id']) ? (int) $rawBody['parent_id'] : null;
        }
        $item = $this->service->update($id, $validated);
        if ($item === null) return $this->error('Category not found', 404);
        return $this->success(CategoryResource::make($item), 'Category updated');
    }

    /**
     * Delete a category by ID.
     *
     * Admin only.
     *
     * DELETE /api/categories/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Empty (204) or error (403/404/422)
     */
    public function delete(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) return $this->error('Invalid id', 422);
        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Category not found', 404);
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
