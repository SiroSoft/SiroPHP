# Metrics API Reference

## Overview

Built-in Prometheus metrics for request count, duration histograms, and memory usage. Exposed at `/metrics` endpoint.

```php
use Siro\Core\Metrics;
```

---

## Setup

```php
// In routes/api.php (auto-enabled)
Metrics::init('siro', true);
Metrics::registerRoute($app->router);

// Now GET /metrics returns Prometheus-formatted data
```

---

## Metrics Collected

| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `http_requests_total` | Counter | `method`, `path`, `status` | Total request count |
| `http_request_duration_ms` | Histogram | `method`, `path` | Request duration buckets |
| `http_request_memory_mb` | Histogram | `method` | Memory usage per request |

---

## Custom Metrics

```php
// Counter
Metrics::counter(
    name: 'orders_created_total',
    value: 1,
    labels: ['status' => 'pending'],
    help: 'Total orders created',
);

// Histogram
Metrics::histogram(
    name: 'payment_processing_ms',
    value: 153.2,
    labels: ['gateway' => 'stripe'],
    help: 'Payment processing time',
    buckets: [50, 100, 200, 500, 1000],
);

// Gauge (current value, not cumulative)
Metrics::gauge(
    name: 'active_users',
    value: 42,
    labels: ['plan' => 'premium'],
    help: 'Currently active users',
);
```

---

## Export Format (OpenMetrics)

```
# HELP http_requests_total Total request count
# TYPE http_requests_total counter
http_requests_total{method="GET",path="/api/products",status="200"} 1542 1712345678

# HELP http_request_duration_ms Request duration in milliseconds
# TYPE http_request_duration_ms histogram
http_request_duration_ms_bucket{method="GET",path="/api/products",le="10"} 890
http_request_duration_ms_bucket{method="GET",path="/api/products",le="50"} 1420
http_request_duration_ms_bucket{method="GET",path="/api/products",le="+Inf"} 1542
http_request_duration_ms_sum{method="GET",path="/api/products"} 28450.5
http_request_duration_ms_count{method="GET",path="/api/products"} 1542
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `init(string $namespace, bool $persist)` | Initialize metrics system |
| `counter(string $name, int $value, array $labels, string $help)` | Increment counter |
| `histogram(string $name, float $value, array $labels, string $help, array $buckets)` | Record observation |
| `gauge(string $name, float\|int $value, array $labels, string $help)` | Set gauge value |
| `export()` | Get OpenMetrics text |
| `registerRoute(Router $router, string $path)` | Auto-register `/metrics` endpoint |
| `persistNow()` | Flush to cache immediately |
