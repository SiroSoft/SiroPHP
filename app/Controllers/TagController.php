<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TagService;
use App\Resources\TagResource;
use Siro\Core\Request;
use Siro\Core\Response;

final class TagController
{
    public function __construct(private readonly TagService $service)
    {
    }

    public function index(Request $request): Response
    {
        $result = $this->service->getAll($request->queryInt('page', 1), $request->queryInt('per_page', 20));
        return Response::paginated(TagResource::collection($result['data']), $result['meta'], 'Tag list');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);
        $item = $this->service->getById($id);
        if ($item === null) return Response::error('Tag not found', 404);
        return Response::success(TagResource::make($item), 'Tag detail');
    }

    public function store(Request $request): Response
    {
        $item = $this->service->create($request->validate(['name' => 'required|min:1|max:100']));
        return Response::created(TagResource::make($item), 'Tag created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);
        $item = $this->service->update($id, $request->validate(['name' => 'required|min:1|max:100']));
        if ($item === null) return Response::error('Tag not found', 404);
        return Response::success(TagResource::make($item), 'Tag updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);
        return $this->service->delete($id)
            ? Response::noContent()
            : Response::error('Tag not found', 404);
    }
}
