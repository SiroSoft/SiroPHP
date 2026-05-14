<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\TagResource;
use App\Services\TagService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class TagController extends Controller
{
    public function __construct(private readonly TagService $service)
    {
    }

    public function index(Request $request): Response
    {
        $result = $this->service->getAll(page: $request->queryInt('page', 1), perPage: $request->queryInt('per_page', 20));
        /** @var array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}} $result */
        return $this->paginated(TagResource::collection($result['data']), $result['meta'], 'Tag list');
    }

    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->getById($id);
        /** @var array<string, mixed>|null $item */
        if ($item === null) return $this->error('Tag not found', 404);
        return $this->success(TagResource::make($item), 'Tag detail');
    }

    public function store(Request $request): Response
    {
        $item = $this->service->create($this->validate(['name' => 'required|min:1|max:100']));
        /** @var array<string, mixed> $item */
        return $this->created(TagResource::make($item), 'Tag created');
    }

    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->update($id, $this->validate(['name' => 'min:1|max:100']));
        /** @var array<string, mixed>|null $item */
        if ($item === null) return $this->error('Tag not found', 404);
        return $this->success(TagResource::make($item), 'Tag updated');
    }

    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Tag not found', 404);
    }
}
