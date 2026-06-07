---
title: B EN CH MA RK
description: SiroPHP B EN CH MA RK reference
sidebar_position: 2
sidebar_label: B EN CH MA RK
---

# Performance Benchmarks

**SiroPHP**: 864K ops/sec (avg) | ~1ms cold boot (Linux) | ~2KB memory per request

---

## How to Run

```bash
# Run from project root
php siro benchmark
```

Or from siro-core:

```bash
php benchmark.php --quick
php benchmark.php --json
```

---

## Latest Results (PHP 8.2.30, Linux, OPcache)

| Benchmark | Avg (ms) | Ops/sec |
|-----------|:--------:|:-------:|
| Container::make | **0.0009** | **1,076,416** |
| Response::success() | **0.0005** | **2,214,612** |
| Route dispatch (static) | **0.0042** | **239,234** |
| Route dispatch (1000 routes) | **0.0023** | O(1) |
| Middleware 5-layer | **0.0083** | **120,481** |
| Validation 5 rules | **0.0070** | **142,857** |
| Cold boot (Linux, OPcache) | **~1ms** | — |
| Cold boot (Linux, no OPcache) | **~3ms** | — |
| Cold boot (Windows, no OPcache) | **~8ms** | — |
| Memory per request | **~2KB** | — |

> "3.1M JSON/sec" is a synthetic micro-benchmark for `Response::success()` construction only,
> not a real HTTP request throughput. Real-world throughput is ~864K ops/sec average.

### Routing Performance

| Benchmark | Avg (ms) | Ops/sec |
|-----------|:--------:|:-------:|
| Dynamic route matching (depth-grouped) | **0.0668** | **14,971** |
| RouteMatcher depth-grouped dispatch | **0.0032** | **312,500** |

> Dynamic route matching: **14,971 ops/sec** — 17x improvement over v0.29.
> RouteMatcher uses depth-grouped matching for O(1) route lookup per depth level.

---

## Comparison

| Metric | SiroPHP | Laravel | Fastify | Gin |
|--------|:-------:|:-------:|:-------:|:---:|
| Boot time (cold) | **~1ms** | ~60ms | ~5ms | **~0.3ms** |
| Boot time (warm) | **~0.3ms** | ~30ms | ~3ms | **~0.3ms** |
| Memory per request | **~2KB** | ~20MB | ~10MB | ~2MB |
| Avg ops/sec | **864K** | ~50K | ~120K | ~500K |
| Dependencies | **0** | 60+ | 15+ | 1 |

> Laravel comparison is framework overhead only, not application-level throughput.
> With real business logic (DB queries, validation, auth), the gap narrows.
