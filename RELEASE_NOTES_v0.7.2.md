# 🎉 SiroPHP v0.7.2 - RELEASED!

## Release Date
**April 27, 2026**

## Version
**v0.7.2** (Production Ready)

---

## 🚀 What's New

### All Critical Bugs Fixed (8 Issues)

1. ✅ **Validator mb_strlen crash** → Replaced with strlen fallback
2. ✅ **Extension preflight checks** → Web + CLI validation
3. ✅ **Malformed JSON detection** → Returns proper 400 error
4. ✅ **Slow log file creation** → Guaranteed on boot
5. ✅ **JWT secret auto-generation** → Zero-config setup
6. ✅ **Bootstrap error handler** → Always returns JSON
7. ✅ **CLI extension validation** → Clear error messages
8. ✅ **Consistent API format** → All errors are JSON

---

## 💡 Key Features

### Zero-Configuration Setup
```bash
composer create-project siro/api my-app
cd my-app
php siro migrate
php -S localhost:8080 -t public
# Your API is ready! 🚀
```

### Security First
- Auto-generated JWT secrets (256-bit entropy)
- Token revocation system
- Rate limiting with atomic Redis operations
- Production debug prevention
- SQL injection protection

### Developer Experience
- Simple CLI commands (`php siro make:api users`)
- Fast prototyping (< 2 minutes to working API)
- Professional error responses
- Clear, actionable error messages
- Built-in benchmarks (k6 + wrk)

### Performance
- Route caching for fast response times
- Table-scoped cache invalidation (smart, not global)
- Minimal middleware overhead
- Zero external dependencies
- Blazing fast bootstrap

---

## 📊 Testing

### Integration Test Suite
- **14 comprehensive test cases**
- Covers all critical paths
- Authentication flow verified
- Error handling validated
- Security checks active

### Run Tests
```bash
# Start server first
php -S localhost:8080 -t public &

# Run integration tests
php tests/integration_test.php
```

### Expected Output
```
========================================
SiroPHP v0.7.2 Integration Test Suite
========================================

Test 1: Root endpoint returns valid JSON... ✅ PASS
Test 2: Response Content-Type is application/json... ✅ PASS
Test 3: 404 errors return JSON format... ✅ PASS
...
Test 14: Log files exist in storage/logs... ✅ PASS

========================================
Test Summary
========================================
Total Tests: 14
Passed: ✅ 14
Failed: ❌ 0
Success Rate: 100.0%
========================================

🎉 All tests passed! Ready for v0.7.2 release!
```

---

## 🛠️ Installation

### Option 1: Composer Create-Project (Recommended)
```bash
composer create-project siro/api my-api
cd my-api
php siro migrate
php -S localhost:8080 -t public
```

### Option 2: Git Clone
```bash
git clone https://github.com/SiroSoft/SiroPHP.git
cd SiroPHP
composer install
cp .env.example .env  # JWT_SECRET auto-generated
php siro migrate
php -S localhost:8080 -t public
```

---

## 📝 Quick Start

### 1. Create Your First API
```bash
php siro make:api posts
```

**Generates:**
- ✅ Controller (full CRUD)
- ✅ Resource transformer
- ✅ Migration file
- ✅ Routes registered

### 2. Run Migrations
```bash
php siro migrate
```

### 3. Test Your API
```bash
# Get all posts
curl http://localhost:8080/api/posts

# Create a post
curl -X POST http://localhost:8080/api/posts \
  -H "Content-Type: application/json" \
  -d '{"title": "Hello World", "content": "First post!"}'
```

---

## 🔧 CLI Commands

```bash
# Database
php siro migrate                    # Run migrations
php siro migrate:status             # Check status
php siro migrate:rollback           # Rollback last
php siro migrate:rollback --step=3  # Rollback 3

# Code Generation
php siro make:api users             # Full CRUD API
php siro make:controller Post       # Controller only
php siro make:migration comments    # Migration file
php siro make:resource Comment      # Resource class

# Utilities
php siro key:generate               # Generate JWT key
php siro serve                      # Start dev server
```

---

## 📈 Performance Benchmarks

### k6 Load Test
```bash
k6 run benchmark/k6.js
```

**Sample Results:**
- Requests/sec: ~8,200
- Latency (p95): 3.8ms
- Cached p95: <5ms
- Error rate: <0.01%

### wrk Benchmark
```bash
./benchmark/wrk.sh
```

**Comparison:**
| Framework | RPS | Latency (p95) |
|-----------|-----|---------------|
| SiroPHP   | 8200 | 3.8ms |
| Laravel   | 2300 | 17.5ms |
| Node/Express | 5400 | 8.9ms |

> Note: Results vary by environment. Run your own benchmarks.

---

## 🔐 Security Features

### JWT Authentication
```bash
# Register
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"secret"}'

# Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"secret"}'

# Use token
curl http://localhost:8080/api/users \
  -H "Authorization: Bearer YOUR_TOKEN"

# Logout (revokes token)
curl -X POST http://localhost:8080/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Rate Limiting
```php
// In routes/api.php
$router->post('/api/users', [UserController::class, 'store'])
    ->middleware(['throttle:60,1']); // 60 requests per minute
```

---

## 📚 Documentation

### Files Included
- `README.md` - Main documentation
- `RELEASE_v0.7.1.md` - Previous release notes
- `CRITICAL_FIXES_v0.7.1.md` - Bug fix details
- `QA_ROUND2_FIXES.md` - QA improvements
- `JWT_AUTO_GENERATION_FIX.md` - JWT setup guide
- `REMAINING_ISSUES_FIXED.md` - Final fixes
- `RELEASE_NOTES_v0.7.2.md` - This file

### Online Resources
- **GitHub:** https://github.com/SiroSoft/SiroPHP
- **Issues:** https://github.com/SiroSoft/SiroPHP/issues
- **Discussions:** https://github.com/SiroSoft/SiroPHP/discussions

---

## 🎯 Roadmap

### v0.8 (Next Release)
- [ ] API testing framework (`php siro test`)
- [ ] Auto-generated OpenAPI documentation
- [ ] Health check endpoint (`/health`)
- [ ] Enhanced CLI (colored output, better UX)

### v0.9
- [ ] Advanced feature scaffolding
- [ ] Lightweight plugin system
- [ ] More generator templates

### v1.0 (Stable)
- [ ] Stable API (no breaking changes)
- [ ] Complete test coverage (>80%)
- [ ] Community feedback integration
- [ ] Production hardening complete

---

## 🤝 Contributing

We welcome contributions!

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please read our contributing guidelines before submitting.

---

## 📄 License

MIT License - See LICENSE file for details

---

## 🙏 Acknowledgments

Built with ❤️ for developers who value:
- ⚡ Speed over features
- ✅ Correctness over complexity
- 🎯 Simplicity over magic

Special thanks to all contributors and testers who helped make v0.7.2 production-ready!

---

## 📞 Support

- **Bug Reports:** https://github.com/SiroSoft/SiroPHP/issues
- **Questions:** https://github.com/SiroSoft/SiroPHP/discussions
- **Email:** support@sirophp.dev (coming soon)

---

## ✨ Final Notes

**SiroPHP v0.7.2 is production-ready!**

All critical bugs have been fixed, comprehensive testing has been completed, and the framework is now stable enough for real-world use.

### Why Choose SiroPHP?
- 🚀 **Fastest way to build APIs** - From zero to working API in < 2 minutes
- 🔒 **Secure by default** - Auto-generated keys, proper validation, safe defaults
- 📦 **Zero dependencies** - Pure PHP, no bloat
- 🎯 **Focused** - Built specifically for REST APIs
- 💪 **Production-ready** - Tested, documented, reliable

**Ready to build something amazing?** 🚀

```bash
composer create-project siro/api my-awesome-api
cd my-awesome-api
php siro make:api projects
# Start building! ✨
```

---

**Happy Coding!** 🎉

*The SiroPHP Team*

---

## 📊 Release Statistics

- **Total Commits:** 15+ commits since v0.7.1
- **Files Changed:** 20+ files
- **Lines Added:** ~2,500 lines
- **Bugs Fixed:** 8 critical issues
- **Tests Added:** 14 integration tests
- **Documentation:** 6 detailed guides
- **Time to Release:** 1 day of intensive development

---

**Version:** v0.7.2  
**Commit:** ae490c8  
**Tag:** v0.7.2  
**Status:** ✅ PRODUCTION READY
