<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Resources\OrderResource;
use Siro\Core\Request;
use Siro\Core\Response;

final class OrderController
{
    public function index(Request $request): Response
    {
        $page = $request->queryInt('page', 1);
        $perPage = $request->queryInt('per_page', 20);
        $status = $request->queryString('status');

        $query = Order::query();
        if ($status !== '') {
            $query = $query->where('status', $status);
        }

        $result = $query->orderBy('created_at', 'DESC')->paginate($perPage, $page);
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

        $order = Order::find($id);
        if ($order === null) return Response::error('Order not found', 404);

        return Response::success(OrderResource::make($order), 'Order detail');
    }

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'customer_name' => 'required|min:2|max:200',
            'customer_email' => 'required|email',
            'total' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,cancelled',
            'items' => 'required|min:1',
        ]);

        $data['items'] = is_array($data['items']) ? json_encode($data['items']) : $data['items'];
        $order = Order::create($data);

        return Response::created(OrderResource::make($order), 'Order created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);

        $order = Order::find($id);
        if ($order === null) return Response::error('Order not found', 404);

        $data = $request->validate([
            'customer_name' => 'min:2|max:200',
            'customer_email' => 'email',
            'total' => 'numeric|min:0',
            'status' => 'in:pending,completed,cancelled',
        ]);

        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        $order->update($data);
        return Response::success(OrderResource::make($order), 'Order updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return Response::error('Invalid id', 422);

        $order = Order::find($id);
        if ($order === null) return Response::error('Order not found', 404);

        $order->delete();
        return Response::noContent();
    }
}
