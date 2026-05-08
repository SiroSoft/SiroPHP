<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\OrderResource;
use App\Services\OrderService;
use Siro\Core\Request;
use Siro\Core\Response;

final class OrderController
{
    public function __construct(private readonly OrderService $service)
    {
    }

    public function index(Request $request): Response
    {
        $page = $request->queryInt('page', 1);
        $perPage = $request->queryInt('per_page', 20);

        $result = $this->service->getAll($request->all(), $page, $perPage);
        return Response::paginated(
            OrderResource::collection($result['data']),
            $result['meta'],
            'Orders list'
        );
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);

        $order = $this->service->getById($id);
        if ($order === null) return Response::error('Order not found', 404);

        return Response::success(OrderResource::make($order), 'Order detail');
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'customer_name' => 'required|min:2|max:200',
            'customer_email' => 'required|email',
            'total' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $items = $request->input('items');
        $validated['items'] = is_array($items) ? $items : (is_string($items) ? $items : '[]');

        $order = $this->service->create($validated);
        return Response::created(OrderResource::make($order), 'Order created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);

        $validated = $request->validate([
            'customer_name' => 'min:2|max:200',
            'customer_email' => 'email',
            'total' => 'numeric|min:0',
            'status' => 'in:pending,completed,cancelled',
        ]);

        $order = $this->service->update($id, $validated);
        if ($order === null) return Response::error('Order not found', 404);

        return Response::success(OrderResource::make($order), 'Order updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);

        return $this->service->delete($id)
            ? Response::noContent()
            : Response::error('Order not found', 404);
    }
}
