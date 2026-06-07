<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\OrderResource;
use App\Role;
use App\Services\OrderService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $service)
    {
    }

    /**
     * List all orders with pagination and optional status/user_id filtering.
     *
     * Non-admin users see only their own orders.
     * Rate limited: 60 requests per minute.
     *
     * GET /api/orders?page=1&per_page=20&status=pending
     *
     * @param Request $request Incoming HTTP request with optional query params
     * @return Response Paginated list of orders
     */
    public function index(Request $request): Response
    {
        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 20)));

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }

        $params = $request->all();
        if ($currentUserRole !== Role::ADMIN) {
            $allowed = ['status', 'user_id'];
            $params = array_intersect_key($params, array_flip($allowed));
            $params['user_id'] = $currentUserId;
        }

        /** @var array<string, mixed> $params */
        $result = $this->service->getAll($params, $page, $perPage);
        $data = [];
        foreach ($result['data'] as $item) {
            $data[] = $item->toArray();
        }
        return $this->paginated(
            OrderResource::collection($data),
            $result['meta'],
            'Orders list'
        );
    }

    /**
     * Get a single order by ID.
     *
     * Non-admin users can only view their own orders.
     *
     * GET /api/orders/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Order detail (200) or error (403/404/422)
     */
    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }

        $order = $this->service->getById($id);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        $orderUserId = is_numeric($order['user_id'] ?? null) ? (int) $order['user_id'] : 0;
        if ($currentUserRole !== Role::ADMIN && $currentUserId !== $orderUserId) {
            return $this->error('Forbidden', 403);
        }

        return $this->success(OrderResource::make($order), 'Order detail');
    }

    /**
     * Create a new order with line items.
     *
     * Validates customer info, items (product existence, price > 0, quantity > 0),
     * calculates total, and sets status to 'pending'.
     *
     * POST /api/orders
     * Body: { customer_name: string, customer_email: string, items: array<{product_id: int, price: float, quantity: int}> }
     *
     * @param Request $request Incoming HTTP request with order data
     * @return Response Created order (201) or error (422)
     */
    public function store(Request $request): Response
    {
        $validated = $this->validate([
            'customer_name' => 'required|min:2|max:200',
            'customer_email' => 'required|email',
        ]);

        $items = $request->input('items');
        if (!is_array($items)) {
            return $this->error('Validation failed', 422, [
                'items' => ['Items must be an array'],
            ]);
        }
        foreach ($items as $i => $item) {
            if (!is_array($item) || !isset($item['product_id'], $item['price'], $item['quantity'])) {
                return $this->error('Validation failed', 422, [
                    "items.$i" => ['Each item must have product_id, price, and quantity'],
                ]);
            }
            $price = $item['price'];
            $quantity = $item['quantity'];
            if (!is_numeric($price) || (float) $price <= 0) {
                return $this->error('Validation failed', 422, [
                    "items.$i.price" => ['Price must be greater than 0'],
                ]);
            }
            if (!is_int($quantity) || $quantity <= 0) {
                return $this->error('Validation failed', 422, [
                    "items.$i.quantity" => ['Quantity must be a positive integer'],
                ]);
            }
            $productId = is_numeric($item['product_id']) ? (int) $item['product_id'] : 0;
            $product = \App\Models\Product::find($productId);
            if ($product === null) {
                return $this->error('Validation failed', 422, [
                    "items.$i.product_id" => ['Product not found'],
                ]);
            }
        }
        $validated['items'] = $items;

        $currentUser = $request->user();
        $currentUserId = 0;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
        }
        $validated['user_id'] = $currentUserId;

        $validated['total'] = $this->calculateTotal($items);
        $validated['status'] = 'pending';

        $order = $this->service->create($validated);
        return $this->created(OrderResource::make($order), 'Order created');
    }

    /**
     * Update customer info on an existing order.
     *
     * Non-admin users can only update their own orders. Partial updates supported.
     *
     * PUT /api/orders/{id}
     * Body: { customer_name?: string, customer_email?: string }
     *
     * @param Request $request Incoming HTTP request with order updates
     * @return Response Updated order (200) or error (403/404/422)
     */
    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }

        $order = $this->service->getById($id);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        $orderUserId = is_numeric($order['user_id'] ?? null) ? (int) $order['user_id'] : 0;
        if ($currentUserRole !== Role::ADMIN && $currentUserId !== $orderUserId) {
            return $this->error('Forbidden', 403);
        }

        $validated = $this->validate([
            'customer_name' => 'min:2|max:200',
            'customer_email' => 'email',
        ]);

        $order = $this->service->update($id, $validated);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        return $this->success(OrderResource::make($order), 'Order updated');
    }

    /**
     * Update the status of an order (e.g. pending -> processing -> shipped -> delivered).
     *
     * Non-admin users can only update their own orders.
     * Allowed statuses: pending, processing, shipped, delivered, cancelled.
     * Rate limited: 60 requests per minute.
     *
     * PATCH /api/orders/{id}/status
     * Body: { status: string }
     *
     * @param Request $request Incoming HTTP request with new status
     * @return Response Updated order (200) or error (403/404/422)
     */
    public function updateStatus(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        $validated = $this->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $currentUser = $request->user();
        $currentUserRole = is_array($currentUser) && isset($currentUser['role']) && is_string($currentUser['role']) ? $currentUser['role'] : Role::USER;

        $order = $this->service->getById($id);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        $orderUserId = is_numeric($order['user_id'] ?? null) ? (int) $order['user_id'] : 0;
        $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
        if ($currentUserRole !== Role::ADMIN && $currentUserId !== $orderUserId) {
            return $this->error('Forbidden', 403);
        }

        $updated = $this->service->update($id, ['status' => $validated['status']]);
        if ($updated === null) {
            return $this->error('Order not found', 404);
        }

        return $this->success(OrderResource::make($updated), 'Order status updated');
    }

    /**
     * Delete an order by ID.
     *
     * Non-admin users can only delete their own orders.
     *
     * DELETE /api/orders/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Empty (204) or error (403/404/422)
     */
    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) {
            return $this->error('Invalid id', 422);
        }

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }

        $order = $this->service->getById($id);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        $orderUserId = is_numeric($order['user_id'] ?? null) ? (int) $order['user_id'] : 0;
        if ($currentUserRole !== Role::ADMIN && $currentUserId !== $orderUserId) {
            return $this->error('Forbidden', 403);
        }

        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Order not found', 404);
    }

    /** @param array<array-key, mixed> $items */
    private function calculateTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            if (is_array($item) && isset($item['price'])) {
                $qty = isset($item['quantity']) && is_numeric($item['quantity']) ? (float) $item['quantity'] : 1.0;
                $price = is_numeric($item['price']) ? (float) $item['price'] : 0.0;
                $total += $price * $qty;
            }
        }
        return round($total, 2);
    }
}
