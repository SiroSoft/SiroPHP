<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\OrderResource;
use App\Services\OrderService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $service)
    {
    }

    public function index(Request $request): Response
    {
        $page = $request->queryInt('page', 1);
        $perPage = $request->queryInt('per_page', 20);

        $result = $this->service->getAll($request->all(), $page, $perPage);
        /** @var array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}} $result */
        return $this->paginated(
            OrderResource::collection($result['data']),
            $result['meta'],
            'Orders list'
        );
    }

    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = 'user';
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : 'user';
        }

        $order = $this->service->getById($id);
        /** @var array<string, mixed>|null $order */
        if ($order === null) return $this->error('Order not found', 404);

        $orderUserId = is_numeric($order['user_id'] ?? null) ? (int) $order['user_id'] : 0;
        if ($currentUserId !== $orderUserId && $currentUserRole !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        return $this->success(OrderResource::make($order), 'Order detail');
    }

    public function store(Request $request): Response
    {
        $validated = $this->validate([
            'customer_name' => 'required|min:2|max:200',
            'customer_email' => 'required|email',
            'total' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $items = $request->input('items');
        if ($items !== null && !is_array($items)) {
            return $this->error('Validation failed', 422, [
                'items' => ['Items must be an array'],
            ]);
        }
        $validated['items'] = is_array($items) ? $items : '[]';

        $order = $this->service->create($validated);
        /** @var array<string, mixed> $order */
        return $this->created(OrderResource::make($order), 'Order created');
    }

    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = 'user';
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : 'user';
        }

        $order = $this->service->getById($id);
        /** @var array<string, mixed>|null $order */
        if ($order === null) return $this->error('Order not found', 404);

        $orderUserId = is_numeric($order['user_id'] ?? null) ? (int) $order['user_id'] : 0;
        if ($currentUserId !== $orderUserId && $currentUserRole !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $validated = $this->validate([
            'customer_name' => 'min:2|max:200',
            'customer_email' => 'email',
            'total' => 'numeric|min:0',
            'status' => 'in:pending,completed,cancelled',
        ]);

        $order = $this->service->update($id, $validated);
        /** @var array<string, mixed>|null $order */
        if ($order === null) return $this->error('Order not found', 404);

        return $this->success(OrderResource::make($order), 'Order updated');
    }

    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = 'user';
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : 'user';
        }

        $order = $this->service->getById($id);
        /** @var array<string, mixed>|null $order */
        if ($order === null) return $this->error('Order not found', 404);

        $orderUserId = is_numeric($order['user_id'] ?? null) ? (int) $order['user_id'] : 0;
        if ($currentUserId !== $orderUserId && $currentUserRole !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Order not found', 404);
    }
}
