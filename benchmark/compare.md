# Benchmark Comparison

## Latest Results (v0.7.10 - April 2026)

Test conditions:
- CPU: Local multi-core machine
- Concurrency: 20 parallel requests
- Total Requests: 5,000 per test
- Duration: ~30-35 seconds
- Endpoint: `GET /` (static JSON response)
- Server: PHP built-in server (`php -S localhost:8080`)
- PHP Version: 8.2.30

### SiroPHP v0.7.10 Performance

| Configuration | RPS | Latency (p95) | Latency (p99) | Success Rate |
|--------------|-----|---------------|---------------|-------------|
| **Without Cache** | 139.85 | 150.23ms | 236.70ms | 100% |
| **With Config Cache** | **158.08** | **118.99ms** | **138.62ms** | **100%** |
| **Improvement** | **+13%** | **-20.8%** | **-41.4%** | - |

### vs Laravel 11.x (Estimated)

| Framework | RPS | Latency (p95) | Memory | Package Size |
|-----------|-----|---------------|--------|-------------|
| **SiroPHP v0.7.10** | **158** | **119ms** | **5-10 MB** | **200 KB** |
| Laravel 11.x | 40-60 | 500-700ms | 40-60 MB | 50 MB |
| **Advantage** | **2.6-4x** | **4-6x** | **6-8x** | **250x** |

> **Note:** Laravel numbers are industry estimates for development mode. Production performance with OPcache + Nginx would be 3-5x higher for both frameworks.

### Expected Production Performance (Nginx + PHP-FPM + OPcache)

| Framework | Estimated RPS | Use Case |
|-----------|--------------|----------|
| **SiroPHP** | 500-800 | APIs, Microservices |
| Laravel | 150-250 | Full-stack apps |

---

## Historical Data (v0.7.1 Sample)

Test conditions (same for all):

- CPU: same local machine
- Concurrency: 100
- Threads: 4
- Duration: 10s
- Endpoint shape: `GET /users` (JSON list)

| Framework | RPS  | Latency (p95) | Notes |
| --------- | ---- | ------------- | ----- |
| Siro      | 8200 | 3.8ms         | Route cache + minimal middleware stack |
| Laravel   | 2300 | 17.5ms        | Full framework bootstrap cost |
| Node      | 5400 | 8.9ms         | Express baseline with equivalent JSON response |

> These values are sample showcase numbers for public comparison format. Re-run in your own environment before publishing final claims.

## How to reproduce

1. Start Siro: `php -S localhost:8080 -t public`
2. Run k6 full mix: `k6 run benchmark/k6.js`
3. Run isolated cached scenario: `SCENARIO=users_cached k6 run benchmark/k6.js`
4. Run wrk: `wrk -t4 -c100 -d10s http://localhost:8080/users`

Expected target:

- API p95 < 20ms
- Cached p95 < 5ms
