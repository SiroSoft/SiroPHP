# Fuzz Testing & Chaos Engineering

SiroPHP includes built-in fuzz testing and chaos engineering tools that require zero external dependencies. These run directly via PHPUnit (fuzz) and a standalone PHP script (chaos).

## Quick Start

```bash
# Run fuzz tests (random mutations on validators, routers, containers, etc.)
php siro test:fuzz

# Run chaos engineering tests (session leaks, null bytes, cache misses, etc.)
php siro test:chaos

# Run property-based tests (validates validation rules never throw exceptions)
php siro test:property
```

## Fuzz Tests (`tests/fuzz/`)

Fuzz tests generate random mutations — invalid JSON, boundary values, SQL injection attempts, and malformed route paths — and verify the framework handles them without crashes. Tests under `tests/fuzz/` include:

| Test | What it mutates |
|---|---|
| `FuzzRouterTest` | Route paths with special chars, empty segments, repeated slashes |
| `FuzzValidatorTest` | Validation rules against null bytes, unicode overflow, type mismatches |
| `FuzzRequestTest` | Request bodies with malformed JSON, boundary values, injection payloads |
| `FuzzModelTest` | Model attribute assignments with unexpected types |
| `FuzzContainerTest` | Container resolution with non-existent keys, circular references |
| `FuzzSqlCompilerTest` | SQL query compilation with edge-case parameters |
| `FuzzJWTTest` | JWT tokens with tampered payloads, expired timestamps |
| `FuzzResponseTest` | Response serialization with deeply nested structures |
| `FuzzOrmRelationsTest` | Relation loading with invalid foreign keys |
| `FuzzEagerLoaderTest` | Eager loading with cyclic/nested relations |
| `FuzzQueryBuilderTest` | Query builder chains with mixed types and null values |

Each test is powered by `@dataProvider` methods that generate thousands of random inputs, defined in `tests/fuzz/FuzzProviders.php`.

```bash
# Direct execution
php vendor/bin/phpunit tests/fuzz/ --no-coverage
```

## Chaos Engineering Tests

The chaos suite (`scripts/chaos-test.php`) tests core components under adversarial conditions:

- **Session persistence**: Verifies no data leaks after `session->destroy()`
- **Null byte injection**: Validates `Validator::make()` handles embedded null bytes gracefully
- **Cache miss**: Confirms `Cache::get()` returns `null` for non-existent keys
- **PII in logs**: Ensures `Logger::debug()` never throws on sensitive data
- **Binary round-trip**: Tests `Encrypter` encrypt/decrypt with 1024-byte random payloads
- **Null event dispatch**: Verifies `Event::emit()` handles `null` payloads
- **Deep dot notation**: Checks `Config::get('nonexistent.deeply.nested.key')` returns `null`

```bash
# Standalone execution
php scripts/chaos-test.php
```

## Property-Based Tests

Property-based tests validate invariants — properties that must hold true for any input:

- **Validation rules never throw**: No matter what input is passed to `Validator::make()`, the result is always an array (never an exception)
- **Route registration never throws**: Registering any path string on any HTTP method always succeeds
- **Container resolution never throws**: Resolving any string key from the container always returns something (null or a valid service)
- **Cache operations never throw**: Getting, setting, or deleting any key always succeeds

These are co-located in `tests/fuzz/` under `Fuzz*Test.php` files and use `@dataProvider` with fuzz input generators.

## CI Integration

```yaml
# GitHub Actions example
- name: Fuzz tests
  run: php vendor/bin/phpunit tests/fuzz/ --no-coverage

- name: Chaos tests
  run: php scripts/chaos-test.php
```

The full fuzz suite completes in under 30 seconds and requires no external services, databases, or network access.
