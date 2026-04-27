# Siro API Framework v0.7.4

Minimal, high-performance PHP micro-framework for REST APIs.

## Why Siro?

- Faster than full-stack frameworks for API-only workloads
- Minimal bootstrap overhead and lightweight request pipeline
- Focused on REST API development (no unnecessary layers)

## Quick Start (Git Clone)

```bash
git clone https://github.com/SiroSoft/SiroPHP.git my-app
cd my-app
composer install
cp .env.example .env
php siro key:generate
php siro migrate
php -S localhost:8080 -t public
```

### Setup permissions (if needed)

```bash
chmod +x benchmark/wrk.sh
```

## Install (Composer create-project)

Once published to Packagist:

```bash
composer create-project siro/api my-app
cd my-app
php siro migrate
php siro serve
```

## CLI usage

```bash
php siro migrate
php siro make:api users
php siro serve
```

## API example

```bash
curl http://localhost:8080/users
```

## Benchmark

Run API server first:

```bash
php -S localhost:8080 -t public
```

### k6

```bash
k6 run benchmark/k6.js
```

Run individual scenarios:

```bash
SCENARIO=root k6 run benchmark/k6.js
SCENARIO=users k6 run benchmark/k6.js
SCENARIO=users_cached k6 run benchmark/k6.js
```

Output includes:

- Requests/sec
- Latency (p95)
- Error rate

Targets:

- API p95 < 20ms
- Cached `/users` p95 < 5ms

### wrk

```bash
./benchmark/wrk.sh
```

Default command used inside script:

```bash
wrk -t4 -c100 -d10s http://localhost:8080/users
```

Output includes:

- Requests/sec
- Latency
- Error rate

## Performance

> Note: The numbers below are sample results from a controlled local environment. Run the benchmarks yourself to validate on your hardware.

See full benchmark notes: [`benchmark/compare.md`](benchmark/compare.md)

| Framework | RPS  | Latency (p95) | Notes |
| --------- | ---- | ------------- | ----- |
| Siro      | 8200 | 3.8ms         | Route cache + minimal middleware stack |
| Laravel   | 2300 | 17.5ms        | Full framework bootstrap cost |
| Node      | 5400 | 8.9ms         | Express baseline |

Under equivalent test shape, Siro is faster than Laravel in this benchmark profile.

## CI

GitHub Actions workflow: `.github/workflows/test.yml`

On every push it runs:

1. `composer install`
2. `php -l` for all PHP files
3. `php siro migrate`
4. start built-in PHP server + runtime smoke checks:
   - `curl -f http://localhost:8080/`
   - `curl -f http://localhost:8080/users`
5. `php tests/verify_v061.php` (PASS/FAIL verification suite)

## Packaging

- **siro/core** (library): `core/`
- **siro/api** (project): `app/`, `routes/`, `config/`, `public/`

Project package requires:

```json
"siro/core": "^0.7"
```
