---
title: ERP Lite Case Study
description: Production API delivery with SiroPHP
sidebar_position: 14
sidebar_label: ERP Lite Case Study
---

# ERP Lite Case Study

ERP Lite is a production-oriented inventory and sales API built on SiroPHP.
It uses service/repository layering, OpenAPI contract checks, locked Composer
deployments, and Next.js/Nuxt admin consumers.

## What shipped

- JWT authentication, RBAC, Turnstile, DemoGuard, and rate limiting.
- Product, inventory, order, purchase, finance, reporting, and settings APIs.
- OpenAPI generation and runtime contract verification.
- Composer-locked backend artifacts with no manual framework-file copying.
- Next.js and Nuxt admin starters consuming the same API contract.

## Evidence

The accepted production benchmark records `1,435 req/s`, `0%` HTTP errors,
p95 `78.40ms`, and p99 `88.92ms` for the concurrent VPS run. See the full
[ERP Lite benchmark](https://github.com/SiroSoft/SiroERP-Lilte/blob/main/docs/BENCHMARK.md)
for methodology and limitations.

The latest backend verification includes `931` PHPUnit tests, PHPStan Level Max,
Composer audit with zero advisories, and `423` passing API contract checks.

## Delivery loop

```text
Generate -> contract-check -> test -> lock Composer -> build artifact
         -> deploy -> health-check -> smoke-test -> benchmark
```

The important operational choice was to ship `composer.lock` and install the
vendor tree from that lock file before building the container. This keeps the
production framework version reproducible even when the VPS cannot reach the
GitHub API during a build.

## Reuse the pattern

Start with the [starter template](../guides/STARTER_TEMPLATE.md), generate a
bounded CRUD module, expose its OpenAPI contract, then connect either the
[Next.js admin starter](https://github.com/SiroSoft/siro-admin-next) or the
[Nuxt admin starter](https://github.com/SiroSoft/siro-admin-nuxt).
