# Benchmark Comparison (v0.7.1)

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
