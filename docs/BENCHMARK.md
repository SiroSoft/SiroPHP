# Performance Benchmarks

**SiroPHP**: 3.1M JSON responses/sec | 0.3ms cold boot | ~4MB RAM per request

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

## Latest Results (PHP 8.2.30)

| Benchmark | Avg (ms) | Ops/sec |
|-----------|:--------:|:-------:|
| Route dispatch | **0.0012** | **829,176** |
| JSON Response | **0.0003** | **3,125,645** |
| Middleware 5-layer | **0.0034** | **297,299** |
| Request validate 5 rules | **0.0067** | **149,687** |

---

## Comparison

| Metric | SiroPHP | Laravel | Fastify | Gin |
|--------|:-------:|:-------:|:-------:|:---:|
| Boot time | **0.3ms** | ~60ms | ~5ms | **0.3ms** |
| Memory | **4MB** | ~20MB | ~10MB | ~2MB |
| JSON/sec | **3.1M** | ~1K | ~1.2M | ~2.5M |
| Dependencies | **0** | 60+ | 15+ | 1 |
