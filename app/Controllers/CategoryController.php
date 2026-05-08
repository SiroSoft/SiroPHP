<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\CategoryResource;
use App\Services\CategoryService;
use Siro\Core\Request;
use Siro\Core\Response;

/**
 * Category management controller.
 *
 * Provides CRUD operations with service/repository layer
 * and dependency injection via constructor.
 *
 * @package App\Controllers
 */

final class CategoryController
{
    public function __construct(private readonly CategoryService $service)
    {
    }

    public function index(Request $request): Response
    {
        $result = $this->service->getAll($request->queryInt('page', 1), $request->queryInt('per_page', 20));
        return Response::paginated(CategoryResource::collection($result['data']), $result['meta'], 'Category list');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);
        $item = $this->service->getById($id);
        if ($item === null) return Response::error('Category not found', 404);
        return Response::success(CategoryResource::make($item), 'Category detail');
    }

    public function store(Request $request): Response
    {
        $item = $this->service->create($request->validate(['name' => 'required|min:2|max:100']));
        return Response::created(CategoryResource::make($item), 'Category created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);
        $item = $this->service->update($id, $request->validate(['name' => 'required|min:2|max:100']));
        if ($item === null) return Response::error('Category not found', 404);
        return Response::success(CategoryResource::make($item), 'Category updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);
        return $this->service->delete($id)
            ? Response::noContent()
            : Response::error('Category not found', 404);
    }
}