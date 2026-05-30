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

    /**
     * List all tags with pagination.
     *
     * Rate limited: 60 requests per minute.
     *
     * GET /api/tags?page=1&per_page=20
     *
     * @param Request $request Incoming HTTP request with optional query params
     * @return Response Paginated list of tags
     */
    public function index(Request $request): Response
    {
        $result = $this->service->getAll(page: max(1, $request->queryInt('page', 1)), perPage: min(100, max(1, $request->queryInt('per_page', 20))));
        $data = [];
        foreach ($result['data'] as $item) {
            $data[] = $item->toArray();
        }
        return $this->paginated(TagResource::collection($data), $result['meta'], 'Tag list');
    }

    /**
     * Get a single tag by ID.
     *
     * GET /api/tags/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Tag detail (200) or error (404/422)
     */
    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->getById($id);
        if ($item === null) return $this->error('Tag not found', 404);
        return $this->success(TagResource::make($item), 'Tag detail');
    }

    /**
     * Create a new tag.
     *
     * Admin only.
     *
     * POST /api/tags
     * Body: { name: string }
     *
     * @param Request $request Incoming HTTP request with tag name
     * @return Response Created tag (201) or error (403/422)
     */
    public function store(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $item = $this->service->create($this->validate(['name' => 'required|min:1|max:100']));
        return $this->created(TagResource::make($item), 'Tag created');
    }

    /**
     * Update an existing tag.
     *
     * Admin only.
     *
     * PUT /api/tags/{id}
     * Body: { name: string }
     *
     * @param Request $request Incoming HTTP request with updated tag name
     * @return Response Updated tag (200) or error (403/404/422)
     */
    public function update(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->update($id, $this->validate(['name' => 'min:1|max:100']));
        if ($item === null) return $this->error('Tag not found', 404);
        return $this->success(TagResource::make($item), 'Tag updated');
    }

    /**
     * Delete a tag by ID.
     *
     * Admin only.
     *
     * DELETE /api/tags/{id}
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
