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

    // Rate limited: 60 requests per minute
    public function index(Request $request): Response
    {
        $result = $this->service->getAll(page: max(1, $request->queryInt('page', 1)), perPage: min(100, max(1, $request->queryInt('per_page', 20))));
        return $this->paginated(CategoryResource::collection($result['data']), $result['meta'], 'Category list');
    }

    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->getById($id);
        if ($item === null) return $this->error('Category not found', 404);
        return $this->success(CategoryResource::make($item), 'Category detail');
    }

    // Admin only.
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
            $validated['sort_order'] = (int) $rawBody['sort_order'];
        }
        if (isset($rawBody['parent_id'])) {
            $validated['parent_id'] = (int) $rawBody['parent_id'];
        }
        $item = $this->service->create($validated);
        return $this->created(CategoryResource::make($item), 'Category created');
    }

    // Admin only.
    public function update(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $rawId = $request->param('id');
        $id = (int) $rawId;
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
            $validated['sort_order'] = (int) $rawBody['sort_order'];
        }
        if (isset($rawBody['parent_id'])) {
            $validated['parent_id'] = (int) $rawBody['parent_id'];
        }
        $item = $this->service->update($id, $validated);
        if ($item === null) return $this->error('Category not found', 404);
        return $this->success(CategoryResource::make($item), 'Category updated');
    }

    // Admin only.
    public function delete(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $rawId = $request->param('id');
        $id = (int) $rawId;
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
