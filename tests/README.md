# SiroPHP Test Suite

## Overview

All tests are written as PHPUnit test classes organized into three categories:

- **Unit Tests** (`tests/unit/`) - Test individual components in isolation (Router, Request, Response, Validator, Middleware)
- **Integration Tests** (`tests/integration/`) - Test component interactions (API endpoints, Database, Events, Lang, Queue, Mail, Cache, Storage)
- **Feature Tests** (`tests/feature/`) - Test specific features (RS256 JWT, Eager Loading, Validation, Route Constraints, Cron, Queue Timeout, Throttling, Resource, Mass Assignment, File Download)

## Directory Structure

```
tests/
├── run-all.php              # Test runner (delegates to PHPUnit)
├── TestCase.php             # Base PHPUnit test class
├── bootstrap.php            # PHPUnit bootstrap
├── db_test_helper.php       # Database helpers
├── README.md                # This file
├── unit/                    # Unit Tests (PHPUnit)
│   ├── AuthMiddlewareTest.php
│   ├── RequestTest.php
│   ├── ResponseTest.php
│   ├── RouterTest.php
│   └── ValidatorTest.php
├── integration/             # Integration Tests (PHPUnit)
│   ├── ApiTest.php
│   ├── DatabaseTest.php
│   ├── EventTest.php
│   ├── GeneralIntegrationTest.php
│   ├── LangTest.php
│   ├── MiddlewareTest.php
│   └── QueueMailTest.php
└── feature/                 # Feature Tests (PHPUnit)
    ├── AdvancedCronTest.php
    ├── EagerLoadingTest.php
    ├── ExtendedValidationTest.php
    ├── FileDownloadTest.php
    ├── MassAssignmentTest.php
    ├── QueueTimeoutTest.php
    ├── ResourcePatternTest.php
    ├── RouteConstraintsTest.php
    ├── Rs256JwtTest.php
    └── ThrottlingMiddlewareTest.php
```

## Running Tests

### Run All Tests (via PHPUnit)
```bash
php tests/run-all.php
```

Or directly:
```bash
vendor/bin/phpunit
```

### Run Specific Suites
```bash
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
vendor/bin/phpunit --testsuite Feature
```

### Run via CLI
```bash
php siro test
```

## Writing Tests

All test classes should:
1. Extend `App\Tests\TestCase`
2. Use `App\Tests\Unit`, `App\Tests\Integration`, or `App\Tests\Feature` namespace
3. Follow PHPUnit conventions (`test*` or `#[Test]` attribute methods)
4. Clean up test data in `tearDown()`
