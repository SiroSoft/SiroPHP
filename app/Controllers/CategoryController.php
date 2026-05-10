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
        return $this->paginated(CategoryResource::collection($result['data']), $result['meta'], 'Category list');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->getById($id);
        if ($item === null) return $this->error('Category not found', 404);
        return $this->success(CategoryResource::make($item), 'Category detail');
    }

    public function store(Request $request): Response
    {
        $item = $this->service->create($this->validate(['name' => 'required|min:2|max:100']));
        return $this->created(CategoryResource::make($item), 'Category created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->update($id, $this->validate(['name' => 'min:2|max:100']));
        if ($item === null) return $this->error('Category not found', 404);
        return $this->success(CategoryResource::make($item), 'Category updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return $this->error('Invalid id', 422);
        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Category not found', 404);
    }
}
