# 📋 Siro Framework — Testing Rules & Checklist v1.0

> Dùng checklist này để đảm bảo code sẵn sàng production trước mỗi release.

---

## 1. 🧪 PHPUnit — Trước tiên, luôn luôn

```bash
# siro-core
cd siro-core && php vendor/bin/phpunit

# SiroPHP
cd SiroPHP && php vendor/bin/phpunit
```

| Cần đạt | Kiểm tra |
|---------|----------|
| ✅ 100% pass | `OK (243 tests, 359 assertions)` |
| ❌ 0 failures | Không có `FAILURES!` |
| ❌ 0 errors | Không có `ERRORS!` |

---

## 2. 🔍 PHPStan Level 6 — Tĩnh phân tích

```bash
# siro-core
php -d memory_limit=512M vendor/bin/phpstan analyse --level=6

# SiroPHP
php -d memory_limit=512M vendor/bin/phpstan analyse --level=6 --no-progress
```

| Cần đạt | Kiểm tra |
|---------|----------|
| ✅ `[OK] No errors` | 0 errors |
| ❌ Nếu lỗi xuất hiện | Fix hoặc regenerate baseline: `--generate-baseline phpstan-baseline.neon` |

---

## 3. 🔐 Security Audit — PHẢI review bằng tay

### 3a. SQL Injection

```php
// File: DB/QueryBuilder.php — function quoteIdentifier()
// Mọi identifier (table, column name) phải qua validate:
if (preg_match('/[^a-zA-Z0-9_.\s\-]/', $identifier)) {
    throw new RuntimeException('Invalid identifier');
}
```

- [ ] Tất cả `ORDER BY`, `GROUP BY`, table name đều qua `quoteIdentifier()`
- [ ] Không có chỗ nào concat string vào SQL mà không qua prepared statement

### 3b. Path Traversal

```php
// File: UploadedFile.php — function store()
// Mọi đường dẫn upload phải chặn ../, ~, đường dẫn tuyệt đối:
$directory = trim($directory, '/\\');
if (str_contains($directory, '..')) throw ...
if (strpbrk($directory, ':\\')) throw ...
```

- [ ] Upload path không cho phép `../`
- [ ] Filename được sanitize, không giữ path components
- [ ] Final path validated trong storage allowed area

### 3c. Request Body Size

```php
// File: Request.php — function fromGlobals()
// Content-Length header có thể giả mạo, PHẢI đọc & kiểm tra body thật:
$body = file_get_contents('php://input');
if (strlen($body) > $maxBodySize) { ... reject ... }
```

- [ ] Content-Length được verify với actual body size
- [ ] Body size check trước khi decode JSON

### 3d. Authentication Logging

```php
// File: Middleware/AuthMiddleware.php
// Mọi authentication failure PHẢI log:
Logger::error('Authentication failed: ... | IP: ' . $request->ip() . ' | Path: ' . $request->path());
```

- [ ] Token invalid/expired → log
- [ ] Wrong password → log
- [ ] IP + Path included

---

## 4. 🌐 Real-User API E2E Tests

```bash
cd SiroPHP && php vendor/bin/phpunit --testsuite=Feature --filter=RealUserApiE2eTest
```

18 tests bao phủ:
- [ ] Health endpoint
- [ ] Auth: register, login, duplicate email, wrong password
- [ ] Validation: 422 trên tất cả resources
- [ ] 404: non-existent resources
- [ ] CRUD đầy đủ: Product, Category, Tag, Order, Post, User
- [ ] Filtering: category, status, price range, search, sort
- [ ] Pagination: meta fields

---

## 5. 🖥️ CLI Commands — Smoke Test

### 5a. System

```bash
php siro --version          # → "SiroPHP v0.16.x"
php siro env:check          # → all OK
php siro route:list         # → danh sách routes
php siro route:search user  # → filter được
php siro route:rules        # → validation rules
```

### 5b. Make/Generate

```bash
php siro make:model Test
php siro make:controller Test
php siro make:service Test
php siro make:repository Test
php siro make:resource Test
php siro make:test Test
php siro make:job Test
php siro make:mail Test
php siro make:event Test
php siro make:factory Test
php siro make:crud testitems --simple
```

- [ ] Tất cả generate thành công (không PHP error)
- [ ] Routes được thêm vào `api.php`

### 5c. Database

```bash
php siro migrate:status  # → danh sách migration
php siro db:show users   # → schema + data
php siro db:seed         # → chạy seeder
```

### 5d. Cleanup sau khi test

```bash
git checkout routes/api.php  # restore routes
# Xoá file đã generate:
rm app/Models/Test*.php app/Controllers/Test*.php app/Services/Test*.php ...
```

---

## 6. ⚡ Performance Benchmark

```bash
# CLI speed (~10 lần)
for i in 1..10; do time php siro route:list; done
# Kỳ vọng: ~70-80ms average

# API speed (dev server)
php siro serve --port=8081 &
for i in 1..10; do curl -o /dev/null -s -w "%{time_total}\n" http://localhost:8081/health; done
# Kỳ vọng: <100ms (sau warm-up)
```

---

## 7. ✅ Pre-Release Checklist

### Cả 2 repo

- [ ] PHPUnit: **458 tests — 100% pass**
- [ ] PHPStan Level 6: **0 errors**
- [ ] E2E: **18/18 tests pass**
- [ ] Real-user API CRUD: **tất cả resources hoạt động**
- [ ] Validation (422): **tất cả endpoints trả về chuẩn**
- [ ] 404: **tất cả resources trả về chuẩn**
- [ ] Auth: register, login, duplicate, wrong password
- [ ] Pagination: **meta fields đầy đủ**
- [ ] Filtering: **category, status, price, search**

### SiroPHP-specific

- [ ] LICENSE file exists (MIT)
- [ ] .editorconfig đúng chuẩn
- [ ] .gitattributes đúng chuẩn
- [ ] composer.json version chính xác
- [ ] README badge test count chính xác
- [ ] CHANGELOG đã cập nhật
- [ ] `routes/api.php` không còn reference controller cũ
- [ ] `phpstan.neon` exclude paths chính xác

### siro-core-specific

- [ ] composer.json version bump
- [ ] CHANGELOG đã cập nhật
- [ ] PHPStan baseline regenerated (nếu thay đổi code)

---

## 8. 🚨 Quick Recovery

```bash
# Khi PHPUnit fail
php vendor/bin/phpunit --filter=FailingTest  # chạy 1 test
php vendor/bin/phpunit --testsuite=Unit       # chạy 1 suite

# Khi PHPStan fail
php -d memory_limit=512M vendor/bin/phpstan analyse --level=6 --generate-baseline

# Khi routes bị hỏng sau khi test make:crud
git checkout routes/api.php
```
