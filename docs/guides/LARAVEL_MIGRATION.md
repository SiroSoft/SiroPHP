---
title: Laravel Migration
description: Move an existing Laravel API to SiroPHP incrementally
sidebar_position: 13
sidebar_label: Laravel Migration
---

# Laravel to SiroPHP

Use a strangler migration. Keep the existing Laravel application serving its
current routes while new or high-throughput API modules move to SiroPHP.

## Choose the boundary

Start with a bounded API surface such as products, health, or read-only
reports. Do not move authentication and writes in the same first cutover.

```text
Client -> gateway -> Laravel routes (existing)
                  -> SiroPHP routes (migrated module)
```

Use the same database only after both applications agree on schema ownership.
Each migration must have one owner and one rollback path.

## Create the SiroPHP service

```bash
composer create-project sirosoft/api siro-api --no-interaction
cd siro-api
php siro key:generate
php siro migrate
```

Pin the framework in the new service and commit its lock file:

```json
{
  "require": {
    "sirosoft/core": "^1.0.10"
  }
}
```

## Map the Laravel layers

| Laravel | SiroPHP | Migration rule |
|---|---|---|
| Route/controller | Route/controller | Keep HTTP parsing and response mapping only |
| FormRequest | Request validation | Preserve field names and error shape |
| Eloquent model | Model | Match table, casts, fillable fields |
| Service/action | Service | Keep business rules and transactions here |
| Repository/query scope | Repository/model query | Keep SQL parameterized |
| API Resource | Resource | Preserve the public JSON contract |
| Feature test | Feature test | Move contract tests before implementation |

Generate the first module and compare responses with Laravel:

```bash
php siro make:crud products
php siro migrate
php siro api:test GET /api/products
```

## Preserve the contract

Export Laravel response fixtures, then assert both implementations have the
same status, envelope, pagination metadata, validation errors, and auth
behavior. Generate SiroPHP's OpenAPI contract after the route is stable:

```bash
php siro make:openapi --with-swagger
php siro api:contract --spec=public/openapi.json
```

Run old and new endpoints behind a feature flag or gateway route. Compare
read-only responses before enabling writes.

## Cut over safely

1. Deploy SiroPHP with read-only routes disabled by default.
2. Replay representative Laravel requests with `php siro api:test`.
3. Enable one route or tenant at a time.
4. Monitor health, traces, SQL timing, and response error rates.
5. Move writes only after idempotency and rollback are tested.
6. Remove the Laravel route only after the SiroPHP path is proven.

## Common traps

- Do not share migrations between services without an explicit owner.
- Do not copy Eloquent models without checking casts and fillable fields.
- Do not change JSON field names during a framework migration.
- Do not use raw SQL for user-controlled filters or sort fields.
- Do not replace Laravel auth tokens without a compatibility window.
