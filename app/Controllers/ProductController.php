<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Resources\ProductResource;
use Siro\Core\Request;
use Siro\Core\Response;

final class ProductController
{
    public function index(Request $request): Response
    {
        $perPage = min($request->queryInt('per_page', 20), 100);
        $page = max($request->queryInt('page', 1), 1);

        $query = Product::query();

        if ($category = $request->query('category')) {
            $query->where('category', '=', $category);
        }

        if ($status = $request->query('status')) {
            $query->where('status', '=', $status);
        }

        $priceMin = $request->query('price_min');
        if ($priceMin !== null && $priceMin !== '') {
            $query->where('price', '>=', (float) $priceMin);
        }

        $priceMax = $request->query('price_max');
        if ($priceMax !== null && $priceMax !== '') {
            $query->where('price', '<=', (float) $priceMax);
        }

        $search = $request->query('search');
        if ($search !== null && $search !== '') {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        $sort = $request->query('sort', 'id');
        $allowedSorts = ['id', 'name', 'price', 'stock', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $order = strtolower($request->query('order', 'desc'));
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }

        $result = $query
            ->orderBy($sort, $order)
            ->paginate($perPage, $page);

        return Response::paginated(
            ProductResource::collection($result['data']),
            $result['meta'],
            'Products list',
        );
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        $item = Product::find($id);
        if ($item === null) {
            return Response::error('Product not found', 404);
        }

        return Response::success(ProductResource::make($item), 'Product fetched');
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|min:1|max:255',
            'description' => 'max:5000000',
            'price' => 'numeric',
            'stock' => 'integer',
            'category' => 'max:100',
            'status' => 'max:20',
        ]);

        $data = array_merge($validated, [
            'price' => (float) ($validated['price'] ?? 0),
            'stock' => (int) ($validated['stock'] ?? 0),
            'status' => $validated['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $item = Product::create($data);
        return Response::created(ProductResource::make($item), 'Product created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        $item = Product::find($id);
        if ($item === null) {
            return Response::error('Product not found', 404);
        }

        $validated = $request->validate([
            'name' => 'min:1|max:255',
            'description' => 'max:65535',
            'price' => 'numeric',
            'stock' => 'integer',
            'category' => 'max:100',
            'status' => 'max:20',
        ]);

        $data = $validated;
        if (isset($data['price'])) {
            $data['price'] = (float) $data['price'];
        }
        if (isset($data['stock'])) {
            $data['stock'] = (int) $data['stock'];
        }

        $item->update($data);
        return Response::success(ProductResource::make($item), 'Product updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            return Response::error('Invalid id', 422);
        }

        $item = Product::find($id);
        if ($item === null) {
            return Response::error('Product not found', 404);
        }

        $item->delete();
        return Response::success(null, 'Product deleted');
    }
}
