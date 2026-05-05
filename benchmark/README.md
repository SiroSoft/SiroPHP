# SiroPHP Benchmark Suite

This directory contains comprehensive benchmark tests and comparison reports for SiroPHP framework.

---

## 📁 Files Overview

### Benchmark Scripts

1. **`simple_benchmark.php`** - PHP-based concurrent HTTP testing
   - Uses cURL multi-handle for parallel requests
   - Provides detailed statistics (min, avg, max, p50, p95, p99)
   - Easy to run: `php benchmark/simple_benchmark.php`

2. **`wrk.sh`** - wrk benchmark script (requires wrk tool)
   - Industry-standard HTTP benchmarking
   - Usage: `bash benchmark/wrk.sh http://localhost:8080/api/users`

3. **`k6.js`** - k6 load testing script (requires k6 tool)
   - Advanced load testing with scenarios
   - Usage: `k6 run benchmark/k6.js`

---

## 📊 Reports & Comparisons

### Latest Results

- **`BENCHMARK_SUMMARY.md`** - Quick overview of v0.7.10 performance
  - Key metrics at a glance
  - Production expectations
  - Optimization tips

- **`BENCHMARK_REPORT_v0.7.10.md`** - Detailed technical report
  - Complete methodology
  - Test environment details
  - Statistical analysis
  - Recommendations

### Framework Comparisons

- **`SIROPHP_VS_LARAVEL_COMPARISON.md`** - Comprehensive comparison
  - Performance benchmarks
  - Feature comparison matrix
  - Cost analysis
  - Use case recommendations
  - Migration strategies

- **`QUICK_COMPARISON_CHART.md`** - Visual comparison charts
  - ASCII bar charts for quick understanding
  - Side-by-side metric comparison
  - Winner highlights

- **`compare.md`** - Historical benchmark data
  - Version-to-version comparisons
  - Latest results prominently displayed

---

## 🚀 Running Benchmarks

### Quick Start (No External Tools Required)

```bash
# 1. Start the server
php -S localhost:8080 -t public

# 2. Run benchmark (in another terminal)
php benchmark/simple_benchmark.php
```

### With wrk (Linux/Mac)

```bash
# Install wrk
# Ubuntu: sudo apt-get install wrk
# Mac: brew install wrk

# Run benchmark
bash benchmark/wrk.sh http://localhost:8080/
```

### With k6 (Cross-platform)

```bash
# Install k6: https://k6.io/docs/getting-started/installation/

# Run full benchmark
k6 run benchmark/k6.js

# Run specific scenario
SCENARIO=users_cached k6 run benchmark/k6.js
```

---

## 📈 Latest Results (v0.7.10)

### Performance Summary

| Configuration | RPS | p95 Latency | Success Rate |
|--------------|-----|-------------|--------------|
| Without Cache | 139.85 | 150.23ms | 100% |
| With Config Cache | **158.08** | **118.99ms** | **100%** |
| Improvement | **+13%** | **-20.8%** | ✅ |

### vs Laravel 11.x

| Metric | SiroPHP | Laravel | Advantage |
|--------|---------|---------|-----------|
| RPS | 158 | 40-60 | **2.6-4x faster** |
| p95 Latency | 119ms | 500-700ms | **4-6x lower** |
| Memory | 5-10 MB | 40-60 MB | **6-8x less** |
| Package Size | 200 KB | 50 MB | **250x smaller** |

---

## 💡 Key Insights

### What We Learned

1. **Config Caching Works**: 13% RPS improvement with `php siro config:cache`
2. **Tail Latency Improved**: p99 reduced by 41% with caching
3. **100% Reliability**: Zero errors across 5,000 requests
4. **Production Potential**: Expected 500-800 RPS with Nginx + OPcache

### Optimization Recommendations

**Immediate:**
- ✅ Enable config cache in production
- ✅ Enable OPcache in php.ini
- ✅ Use Nginx/Apache instead of built-in server

**Future (v0.8.x):**
- 🔄 Implement route caching
- 🔄 Add model schema caching
- 🔄 Response compression (Gzip/Brotli)

---

## 🎯 When to Use These Benchmarks

### For Decision Making

- **Choosing a framework**: Compare SiroPHP vs Laravel performance
- **Infrastructure planning**: Estimate server requirements
- **Cost optimization**: Calculate hosting savings

### For Marketing

- **Landing page**: Showcase performance advantages
- **Documentation**: Prove framework efficiency
- **Investor pitches**: Demonstrate technical superiority

### For Development

- **Performance regression**: Track changes across versions
- **Optimization validation**: Measure impact of improvements
- **Capacity planning**: Determine scaling needs

---

## 📝 Contributing

To add new benchmarks:

1. Create test script in this directory
2. Document methodology and environment
3. Include comparison with previous versions
4. Update `compare.md` with latest results
5. Create summary document if significant findings

---

## 🔗 Related Documentation

- [Main README](../README.md) - Framework overview
- [Installation Guide](../README.md#installation) - Setup instructions
- [Performance Section](../README.md#performance) - Optimization tips

---

## 📞 Questions?

For questions about benchmarks or performance:
- Check `BENCHMARK_SUMMARY.md` for quick answers
- Read `BENCHMARK_REPORT_v0.7.10.md` for detailed analysis
- Review `SIROPHP_VS_LARAVEL_COMPARISON.md` for framework comparison

---

**Last Updated:** April 29, 2026  
**Framework Version:** SiroPHP v0.7.10  
**Status:** ✅ All benchmarks passing
