# 🎉 SiroPHP v0.7.1 - RELEASED!

## Release Information

- **Version:** v0.7.1
- **Release Date:** April 27, 2026
- **Commit:** 73f1f91
- **Tag:** v0.7.1 (annotated)
- **Repository:** https://github.com/SiroSoft/SiroPHP

---

## What's New in v0.7.1

### 🔧 Critical Fixes
- ✅ Fixed `core/composer.json` JSON syntax error (leading character removed)
- ✅ Increased CI timeout from 1 to 5 minutes for reliability
- ✅ Removed placeholder VCS repository URL
- ✅ Added benchmark disclaimer to README

### 📚 Documentation Improvements
- ✅ Improved quick start instructions with migration step
- ✅ Added permission setup documentation (`chmod +x`)
- ✅ Clear benchmark result disclaimers
- ✅ Complete installation guide

### 🛠️ Tooling & Infrastructure
- ✅ GitHub Actions CI/CD pipeline configured
- ✅ Benchmark scripts ready (k6 + wrk)
- ✅ Verification suite complete (`tests/verify_v061.php`)
- ✅ Storage directories with .gitkeep files

---

## Key Features (v0.7.1)

### Authentication & Security
- 🔐 JWT authentication with HS256 (zero dependencies)
- 🔄 Token revocation via version tracking
- 🚪 Logout endpoint with instant token invalidation
- ⚡ Rate limiting with atomic Redis Lua scripts
- 🔒 Strong JWT secret validation (≥32 chars, no placeholders)
- 🛡️ Production debug prevention (`APP_ENV=production` check)
- 🌐 CORS middleware with configurable origins

### Database & Caching
- 💾 Fluent QueryBuilder with JOIN support
- 🗄️ Table-scoped cache invalidation (smart, not global flush)
- 🔄 Database transactions with nested savepoint support
- 📦 Migration system with batch tracking
- ↩️ Rollback support with `--step=N` parameter
- 📊 Migration status command (`php siro migrate:status`)

### Developer Experience
- ⚡ Route caching for fast response times
- 🎯 Simple CLI commands (`php siro make:api users`)
- 📝 Auto-generated API documentation ready
- 🧪 Built-in verification suite
- 📈 Performance benchmarks (k6 + wrk)
- 📋 Structured logging (request, error, slow queries)

### Code Quality
- ✨ Zero external dependencies
- 🏗️ PSR-4 autoloading
- 🔍 Type hints and strict types everywhere
- 🎨 Consistent coding style
- 🧹 Clean architecture (separation of concerns)

---

## Performance Benchmarks

| Metric | Value | Notes |
|--------|-------|-------|
| Requests/sec | ~8,200 | GET /users endpoint |
| Latency (p95) | 3.8ms | With route caching |
| Cached p95 | <5ms | Second request (cache hit) |
| Error rate | <0.01% | Under load test |

> **Note:** These are sample results from controlled local environment. Run benchmarks on your own hardware for accurate results.

**Benchmark Tools Included:**
- `benchmark/k6.js` - Advanced load testing with scenarios
- `benchmark/wrk.sh` - Simple HTTP benchmarking
- `benchmark/compare.md` - Comparison with Laravel & Node.js

---

## Installation

### Quick Start (Local Development)

```bash
# Clone or download
git clone https://github.com/SiroSoft/SiroPHP.git
cd SiroPHP

# Install dependencies
composer install

# Setup environment
cp .env.example .env
# Edit .env with your settings (especially JWT_SECRET)

# Run migrations
php siro migrate

# Start server
php -S localhost:8080 -t public

# Test it
curl http://localhost:8080/
curl http://localhost:8080/users
```

### Create Project (Composer)

```bash
composer create-project siro/api my-app
cd my-app
cp .env.example .env
php siro migrate
php -S localhost:8080 -t public
```

---

## CLI Commands

```bash
# Database
php siro migrate                    # Run pending migrations
php siro migrate:status             # Check migration status
php siro migrate:rollback           # Rollback last migration
php siro migrate:rollback --step=3  # Rollback last 3 migrations

# Code Generation
php siro make:api users             # Generate full CRUD API
php siro make:controller Post       # Generate controller only
php siro make:resource Comment      # Generate resource transformer
php siro make:migration posts       # Generate migration file

# Testing & Verification
php tests/verify_v061.php           # Run verification suite

# Benchmarks
k6 run benchmark/k6.js              # Run k6 load test
./benchmark/wrk.sh                  # Run wrk benchmark
SCENARIO=users_cached k6 run benchmark/k6.js  # Test cached endpoint
```

---

## API Examples

### Health Check
```bash
curl http://localhost:8080/
```

**Response:**
```json
{
  "success": true,
  "message": "Siro API Framework v0.7.1 is running",
  "data": {
    "name": "Siro API Framework",
    "version": "0.7.1",
    "php": "8.2.x"
  },
  "meta": []
}
```

### User Registration
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secret123"
  }'
```

### User Login
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "secret123"
  }'
```

### Protected Endpoint
```bash
curl http://localhost:8080/api/users \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Logout (Token Revocation)
```bash
curl -X POST http://localhost:8080/api/auth/logout \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Configuration

### Environment Variables (.env)

```env
# Application
APP_NAME="Siro API Framework"
APP_ENV=local
APP_DEBUG=true

# Security
JWT_SECRET=your_strong_secret_here_min_32_chars
JWT_TTL=3600

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siro
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=file
CACHE_TTL=60
CACHE_PREFIX=siro:

# Redis (optional, for rate limiting)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# Rate Limiting
THROTTLE_FALLBACK=file

# CORS
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
```

---

## Project Structure

```
SiroPHP/
├── app/
│   ├── Controllers/        # API controllers
│   ├── Middleware/         # Request middleware
│   ├── Resources/          # Response transformers
│   └── Services/           # Business logic
├── core/
│   ├── Auth/              # JWT authentication
│   ├── Cache/             # Cache drivers (File, Redis)
│   ├── Commands/          # CLI commands
│   ├── DB/                # QueryBuilder
│   ├── App.php            # Application bootstrap
│   ├── Router.php         # HTTP router
│   ├── Request.php        # Request handling
│   ├── Response.php       # Response handling
│   └── ...
├── database/
│   └── migrations/        # Database migrations
├── benchmark/
│   ├── k6.js             # k6 load test script
│   ├── wrk.sh            # wrk benchmark script
│   └── compare.md        # Performance comparison
├── tests/
│   └── verify_v061.php   # Verification suite
├── storage/
│   ├── cache/            # File cache storage
│   └── logs/             # Application logs
├── routes/
│   └── api.php           # API route definitions
├── public/
│   └── index.php         # Entry point
├── config/
│   └── database.php      # Database configuration
├── composer.json         # Project dependencies
├── .env.example          # Environment template
└── README.md             # Documentation
```

---

## CI/CD Pipeline

GitHub Actions workflow runs on every push:

1. ✅ Checkout code
2. ✅ Setup PHP 8.2
3. ✅ Install Composer dependencies
4. ✅ Lint all PHP files (`php -l`)
5. ✅ Run database migrations
6. ✅ Start development server
7. ✅ Smoke test endpoints (curl)
8. ✅ Run verification suite

**Workflow file:** `.github/workflows/test.yml`  
**Timeout:** 5 minutes

---

## Testing

### Verification Suite
```bash
php tests/verify_v061.php
```

Checks:
- Required files exist
- Authentication flow works
- Cache functionality operational
- Rate limiting active
- Migration system working
- All components integrated

### Load Testing
```bash
# Full mix scenario
k6 run benchmark/k6.js

# Specific scenarios
SCENARIO=root k6 run benchmark/k6.js
SCENARIO=users k6 run benchmark/k6.js
SCENARIO=users_cached k6 run benchmark/k6.js

# wrk benchmark
./benchmark/wrk.sh
```

---

## Roadmap

### v0.8 (Next Release)
- [ ] Enhanced code generator (with tests & docs)
- [ ] Mini API test framework (`php siro test`)
- [ ] Auto OpenAPI documentation generation
- [ ] Health check endpoint (`/health`)
- [ ] CLI improvements (colored output, better UX)

### v0.9
- [ ] Advanced feature scaffolding
- [ ] Lightweight plugin system
- [ ] More generator templates

### v1.0 (Stable Release)
- [ ] Stable API (no breaking changes)
- [ ] Complete test coverage (>80%)
- [ ] Community feedback integration
- [ ] Production hardening

---

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## License

MIT License - See LICENSE file for details

---

## Support

- **Issues:** https://github.com/SiroSoft/SiroPHP/issues
- **Discussions:** https://github.com/SiroSoft/SiroPHP/discussions
- **Documentation:** README.md

---

## Acknowledgments

Built with ❤️ for developers who value:
- ⚡ Speed over features
- ✅ Correctness over complexity
- 🎯 Simplicity over magic

---

**Happy Coding! 🚀**

*The SiroPHP Team*
