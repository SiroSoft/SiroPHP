# Contributing to SiroPHP

## Development Setup

```bash
git clone https://github.com/SiroSoft/SiroPHP.git
cd SiroPHP
composer install
cp .env.example .env
php siro key:generate
php siro migrate
```

## Running Tests

### All Tests
```bash
php siro test
```

### Individual Test Suites
```bash
# Core tests
php tests/router_request_test.php
php tests/validator_model_test.php
php tests/querybuilder_test.php
php tests/event_test.php
php tests/lang_test.php
php tests/jwt_logger_cache_test.php
php tests/final_core_test.php
php tests/remaining_test.php
php tests/v087_test.php

# Integration
php tests/integration_test.php

# Queue & Mail
php tests/queue_mail_test.php

# Middleware & Edge Cases
php tests/middleware_edge_test.php

# Soft Deletes & API Versioning
php tests/softdelete_version_test.php

# Products API
php tests/products_test.php

# Stability & Load (1,200+ requests)
php tests/stability_test.php

# Error Scenarios
php tests/error_scenario_test.php

# Coverage (77 tests across Console, Middleware, Cache, Mail, Router)
php tests/coverage_test.php

# Service layer
php tests/UserService_test.php
```

### PHPStan Static Analysis
```bash
php vendor/bin/phpstan analyse --level 6
```

### PHPUnit Tests
```bash
php vendor/bin/phpunit
```

### Benchmark
```bash
php -S localhost:8080 -t public
# In another terminal:
php benchmark/simple_benchmark.php
```

## Code Standards

- **PHP 8.2+** with strict types (`declare(strict_types=1)`)
- **PSR-4** autoloading: `App\` → `app/`
- **PSR-12** coding style
- **Test-first** for new features
- **No external dependencies** for core framework

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feat/my-feature`)
3. Write tests first, then implement
4. Ensure all tests pass (`php siro test`)
5. Run PHPStan (`php vendor/bin/phpstan analyse --level 6`)
6. Submit PR with description of changes

## Adding New Commands

1. Create command class in `siro-core/Commands/YourCommand.php`
2. Use the `CommandSupport` trait
3. Register in `siro-core/Console.php` switch statement
4. Add help text in `printHelp()`
5. Write tests in `tests/`

## Adding New Validator Rules

1. Add rule logic in `siro-core/Validator.php` inside `make()`
2. Add fallback message in `fallback()` method
3. Add translation key in language files
4. Write tests in `tests/validator_model_test.php`

## Adding New Middleware

1. Create class in `app/Middleware/` or `siro-core/Middleware/`
2. Implement `handle(Request, callable $next): mixed`
3. Register alias in `public/index.php` via `Router::setMiddlewareAliases()`
4. Write tests in `tests/middleware_edge_test.php` or `tests/coverage_test.php`

## Release Process

1. Update version in `routes/api.php` root endpoint
2. Update `composer.json` versions
3. Create/update `RELEASE_vX.X.X.md`
4. Update `CHANGELOG.md`
5. Tag release (`git tag vX.X.X && git push --tags`)
6. Submit to Packagist

## Project Structure

```
siro-core/          # Core library (packaged as sirosoft/core)
  Auth/             # JWT authentication
  Cache/            # File + Redis cache drivers
  Commands/         # 48 CLI commands
  DB/               # QueryBuilder, ModelQueryBuilder, SoftDeletes, Relations
  Middleware/       # Core middleware (removed Throttle — consolidated in app)

SiroPHP/            # Application skeleton (packaged as sirosoft/api)
  app/
    Controllers/    # AuthController, UserController, ProductController
    Middleware/     # Auth, Cors, Json, Throttle
    Models/         # User, Product
    Resources/      # UserResource, ProductResource
    Services/       # User service
    Jobs/           # Example jobs
    Mails/          # Example mail templates
    Events/         # Example events
  routes/
    api.php         # Route definitions
  tests/            # 18 test files (~427 tests)
  config/
    database.php
```
