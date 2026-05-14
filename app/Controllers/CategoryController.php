<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\CategoryResource;
use App\Services\CategoryService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service)
    {
    }

    public function index(Request $request): Response
    {
        $result = $this->service->getAll(page: $request->queryInt('page', 1), perPage: $request->queryInt('per_page', 20));
        /** @var array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}} $result */
        return $this->paginated(CategoryResource::collection($result['data']), $result['meta'], 'Category list');
    }

    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->getById($id);
        /** @var array<string, mixed>|null $item */
        if ($item === null) return $this->error('Category not found', 404);
        return $this->success(CategoryResource::make($item), 'Category detail');
    }

    public function store(Request $request): Response
    {
        $item = $this->service->create($this->validate(['name' => 'required|min:2|max:100']));
        /** @var array<string, mixed> $item */
        return $this->created(CategoryResource::make($item), 'Category created');
    }

    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->update($id, $this->validate(['name' => 'min:2|max:100']));
        /** @var array<string, mixed>|null $item */
        if ($item === null) return $this->error('Category not found', 404);
        return $this->success(CategoryResource::make($item), 'Category updated');
    }

    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);
        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Category not found', 404);
    }
}
