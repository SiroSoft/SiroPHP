# E-Commerce API Example

A complete e-commerce API built with SiroPHP demonstrating cart management, checkout flow, payment processing, and webhooks.

## Models

### Product

| Field | Type | Rules |
|-------|------|-------|
| id | integer | auto-increment |
| name | string | required, min:1, max:255 |
| description | string | max:65535 |
| price | float | numeric, min:0 |
| stock | integer | min:0 |
| category | string | max:100 |
| status | string | max:20 |
| created_at | datetime | auto |
| updated_at | datetime | auto |

### Category

| Field | Type | Rules |
|-------|------|-------|
| id | integer | auto-increment |
| name | string | required, min:2, max:100 |
| created_at | datetime | auto |

### Order

| Field | Type | Rules |
|-------|------|-------|
| id | integer | auto-increment |
| user_id | integer | foreign key |
| customer_name | string | required, min:2, max:200 |
| customer_email | string | required, email |
| total | float | required, numeric, min:0 |
| status | string | in:pending,completed,cancelled |
| items | json | array of order items |
| created_at | datetime | auto |
| updated_at | datetime | auto |

### OrderItem

| Field | Type | Description |
|-------|------|-------------|
| product_id | integer | Reference to product |
| product_name | string | Snapshot of product name |
| quantity | integer | Quantity ordered |
| unit_price | float | Price at time of order |
| subtotal | float | quantity * unit_price |

### Payment

| Field | Type | Description |
|-------|------|-------------|
| id | integer | auto-increment |
| order_id | integer | Reference to order |
| amount | float | Payment amount |
| status | string | pending,completed,failed |
| method | string | card,bank_transfer,vnpay |
| transaction_id | string | External payment reference |
| created_at | datetime | auto |
| updated_at | datetime | auto |

## API Endpoints

### Products

```
GET    /api/products              List products (paginated, filterable)
GET    /api/products/{id}         Get product detail
POST   /api/products              Create product (protected)
PUT    /api/products/{id}         Update product (protected)
DELETE /api/products/{id}         Delete product (protected)
```

### Categories

```
GET    /api/categories            List categories (paginated)
GET    /api/categories/{id}       Get category detail
POST   /api/categories            Create category (protected)
PUT    /api/categories/{id}       Update category (protected)
DELETE /api/categories/{id}       Delete category (protected)
```

### Orders

```
GET    /api/orders                List user's orders (protected)
GET    /api/orders/{id}           Get order detail (protected, owner/admin)
POST   /api/orders                Create order / Checkout (protected)
PUT    /api/orders/{id}           Update order (protected, owner/admin)
DELETE /api/orders/{id}           Cancel order (protected, owner/admin)
```

## Cart Management

The cart is managed client-side. When the user is ready to checkout, the cart contents are sent to the order creation endpoint.

### Cart Data Structure

```json
{
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        },
        {
            "product_id": 3,
            "quantity": 1
        }
    ]
}
```

### Checkout (Create Order)

```http
POST /api/orders
Authorization: Bearer eyJhbGciOiJSUzI1NiIs...
Content-Type: application/json

{
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "total": 49.99,
    "status": "pending",
    "items": [
        {
            "product_id": 1,
            "name": "Widget Pro",
            "quantity": 2,
            "unit_price": 19.99,
            "subtotal": 39.98
        },
        {
            "product_id": 3,
            "name": "Gadget X",
            "quantity": 1,
            "unit_price": 10.01,
            "subtotal": 10.01
        }
    ]
}
```

Response `201`:
```json
{
    "success": true,
    "message": "Order created",
    "data": {
        "id": 42,
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "total": 49.99,
        "status": "pending",
        "items": [
            {
                "product_id": 1,
                "name": "Widget Pro",
                "quantity": 2,
                "unit_price": 19.99,
                "subtotal": 39.98
            },
            {
                "product_id": 3,
                "name": "Gadget X",
                "quantity": 1,
                "unit_price": 10.01,
                "subtotal": 10.01
            }
        ],
        "created_at": "2026-05-15T10:00:00Z",
        "updated_at": "2026-05-15T10:00:00Z"
    }
}
```

## Payment Processing Pattern

SiroPHP payments follow an asynchronous pattern with order status tracking.

### Payment Flow

```
1. POST /api/orders → Create order (status: pending)
2. Client processes payment externally (e.g., VNPay, Stripe)
3. Payment gateway calls back your webhook URL
4. Webhook updates payment and order status
5. Client polls GET /api/orders/{id} for status updates
```

### Payment Data Structure

```json
{
    "id": 1,
    "order_id": 42,
    "amount": 49.99,
    "status": "completed",
    "method": "card",
    "transaction_id": "txn_abc123",
    "created_at": "2026-05-15T10:05:00Z",
    "updated_at": "2026-05-15T10:05:30Z"
}
```

## Webhook Handling Example

SiroPHP can handle payment webhooks using the built-in routing system. Below is an example of a payment webhook handler.

### Webhook Endpoint

```php
// routes/api.php
$router->post('/webhook/payment', function (Request $request): Response {
    $payload = $request->all();

    // Verify webhook signature
    $signature = $request->header('X-Webhook-Signature');
    $secret = getenv('PAYMENT_WEBHOOK_SECRET');
    $expected = hash_hmac('sha256', json_encode($payload), $secret);

    if (!hash_equals($expected, $signature)) {
        return Response::error('Invalid signature', 401);
    }

    // Extract payment data
    $orderId = (int) ($payload['order_id'] ?? 0);
    $transactionId = $payload['transaction_id'] ?? '';
    $status = $payload['status'] ?? '';

    // Update order status based on payment
    if ($status === 'completed') {
        Database::table('orders')
            ->where('id', '=', $orderId)
            ->update(['status' => 'completed']);
    } elseif ($status === 'failed') {
        Database::table('orders')
            ->where('id', '=', $orderId)
            ->update(['status' => 'cancelled']);
    }

    // Record payment
    Database::table('payments')->insert([
        'order_id' => $orderId,
        'amount' => $payload['amount'] ?? 0,
        'status' => $status,
        'method' => $payload['method'] ?? '',
        'transaction_id' => $transactionId,
        'created_at' => date('c'),
        'updated_at' => date('c'),
    ]);

    return Response::success(null, 'Webhook processed');
})->middleware([JsonMiddleware::class]);
```

### Webhook Payload Example (from payment gateway)

```http
POST /webhook/payment
Content-Type: application/json
X-Webhook-Signature: a1b2c3d4e5f6...

{
    "event": "payment.completed",
    "order_id": 42,
    "transaction_id": "txn_abc123",
    "amount": 49.99,
    "currency": "USD",
    "status": "completed",
    "method": "card",
    "timestamp": "2026-05-15T10:05:30Z"
}
```

## Filtering Products

Products support filtering by various fields:

```http
# By category
GET /api/products?category=electronics

# By status
GET /api/products?status=active

# By price range
GET /api/products?price_min=10&price_max=100

# Search by name
GET /api/products?search=widget

# Combined filters
GET /api/products?category=electronics&status=active&price_min=10&price_max=200&page=1&per_page=20
```

## Complete Checkout Workflow

```bash
# 1. Authenticate
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"secret123"}' | jq -r '.data.token')

# 2. Browse products
curl -s "http://localhost:8080/api/products?category=electronics&status=active" | jq .

# 3. Get product details
curl -s http://localhost:8080/api/products/1 | jq .

# 4. Create order (checkout)
ORDER=$(curl -s -X POST http://localhost:8080/api/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "total": 49.99,
    "status": "pending",
    "items": [
      {"product_id": 1, "name": "Widget Pro", "quantity": 2, "unit_price": 19.99, "subtotal": 39.98},
      {"product_id": 3, "name": "Gadget X", "quantity": 1, "unit_price": 10.01, "subtotal": 10.01}
    ]
  }')
echo $ORDER | jq .
ORDER_ID=$(echo $ORDER | jq '.data.id')

# 5. Poll order status (simulating payment processing)
sleep 2
curl -s http://localhost:8080/api/orders/$ORDER_ID \
  -H "Authorization: Bearer $TOKEN" | jq .

# 6. List all orders for the user
curl -s http://localhost:8080/api/orders \
  -H "Authorization: Bearer $TOKEN" | jq .

# 7. Admin can see all orders
curl -s http://localhost:8080/api/orders?page=1&per_page=50 \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq .

# 8. Cancel an order
curl -X DELETE http://localhost:8080/api/orders/$ORDER_ID \
  -H "Authorization: Bearer $TOKEN"

# 9. Manage products (admin)
curl -s -X POST http://localhost:8080/api/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Product",
    "description": "A brand new product",
    "price": 29.99,
    "stock": 100,
    "category": "electronics",
    "status": "active"
  }' | jq .

# 10. Update stock
curl -s -X PUT http://localhost:8080/api/products/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"stock": 150}' | jq .

# 11. Simulate webhook call
curl -X POST http://localhost:8080/webhook/payment \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $(echo -n '{"event":"payment.completed","order_id":42,"transaction_id":"txn_abc123","amount":49.99,"currency":"USD","status":"completed","method":"card","timestamp":"2026-05-15T10:05:30Z"}' | openssl dgst -sha256 -hmac "your-webhook-secret" | awk '{print $2}')" \
  -d '{
    "event": "payment.completed",
    "order_id": 42,
    "transaction_id": "txn_abc123",
    "amount": 49.99,
    "currency": "USD",
    "status": "completed",
    "method": "card",
    "timestamp": "2026-05-15T10:05:30Z"
  }' | jq .
```

## Order Status Transitions

```
pending → completed (payment received)
pending → cancelled (user cancelled or payment failed)
completed → (terminal state)
cancelled → (terminal state)
```

## Error Handling

### Validation Error (422)

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "customer_name": ["The customer name field is required."],
        "total": ["The total must be a number."]
    }
}
```

### Not Found (404)

```json
{
    "success": false,
    "message": "Product not found"
}
```

### Forbidden (403)

```json
{
    "success": false,
    "message": "Forbidden"
}
```

## Rate Limiting

| Endpoint | Limit |
|----------|-------|
| Product CRUD | 60 requests/minute |
| Order CRUD | 60 requests/minute |
| Public GET | 120 requests/minute |
| Auth endpoints | 30-60 requests/minute |
| Webhook | No limit |

## Relations

- A **Product** belongs to a **Category**
- An **Order** has many **OrderItems**
- An **Order** belongs to a **User**
- An **Order** has one **Payment**
