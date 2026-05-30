<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\TagResource;
use App\Role;
use App\Services\TagService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class TagController extends Controller
{
    public function __construct(private readonly TagService $service)
    {
    }

    // Rate limited: 60 requests per minute
    public function index(Request $request): Response
    {
        $result = $this->service->getAll(page: max(1, $request->queryInt('page', 1)), perPage: min(100, max(1, $request->queryInt('per_page', 20))));
        return $this->paginated(TagResource::collection($result['data']), $result['meta'], 'Tag list');
    }

    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->getById($id);
        if ($item === null) return $this->error('Tag not found', 404);
        return $this->success(TagResource::make($item), 'Tag detail');
    }

    // Admin only.
    public function store(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $item = $this->service->create($this->validate(['name' => 'required|min:1|max:100']));
        return $this->created(TagResource::make($item), 'Tag created');
    }

    // Admin only.
    public function update(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->update($id, $this->validate(['name' => 'min:1|max:100']));
        if ($item === null) return $this->error('Tag not found', 404);
        return $this->success(TagResource::make($item), 'Tag updated');
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
            : $this->error('Tag not found', 404);
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
