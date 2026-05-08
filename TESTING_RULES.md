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

## 3. 🔐 Security Audit — OWASP Top 10

> Kiểm tra từng mục, ghi chú kết quả PASS/FAIL bên cạnh.

### 3.1 A01: Broken Access Control

| Test | Cách test | PASS |
|------|-----------|------|
| IDOR — user A sửa user B | `PUT /api/users/2` với token user 1 → 403 | ☐ |
| Truy cập admin API không token | `POST /api/users` không auth → 401 | ☐ |
| RBAC — user thường gọi mutation | `POST /api/products` không admin → 401/403 | ☐ |
| Tự động OPTIONS preflight | `OPTIONS /api/products` → 204 + CORS headers | ☐ |
| Xoá resource không tồn tại | `DELETE /api/users/99999` → 404, không crash | ☐ |

### 3.2 A02: Cryptographic Failures

| Test | Cách test | PASS |
|------|-----------|------|
| JWT algorithm confusion | Gửi token alg=none → reject | ☐ |
| JWT weak secret | Kiểm tra JWT_SECRET >= 32 ký tự | ☐ |
| Encryption key auto-resolve | Encrypter dùng APP_KEY hoặc JWT_SECRET | ☐ |
| Password hashing | User::create → password_hash(..., PASSWORD_DEFAULT) | ☐ |
| Token versioning | Logout → increment token_version → token cũ hết hạn | ☐ |

### 3.3 A03: Injection

| Test | Cách test | PASS |
|------|-----------|------|
| SQLi — identifier | `ORDER BY 1;DROP TABLE users--` → rejected by `quoteIdentifier()` | ☐ |
| SQLi — prepared statement | `email = 'admin@test.com\' OR 1=1--` → safe (prepared) | ☐ |
| XSS — response đầu ra | `name = <script>alert(1)</script>` → escaped | ☐ |
| Log injection | `name = "foo\nFake log entry"` → newlines escaped | ☐ |
| Header injection | `name` chứa CRLF → blocked | ☐ |
| Mass assignment | Gửi `is_admin=1` khi register → không map được | ☐ |

### 3.4 A04: Insecure Design

| Test | Cách test | PASS |
|------|-----------|------|
| Rate limiting | POST `/api/auth/login` 100 lần/phút → 429 | ☐ |
| Input validation | String field gửi array → 422 | ☐ |
| File upload type | Upload .php → reject hoặc không execute | ☐ |
| File upload size | File >2MB → 413 | ☐ |
| Pagination DOS | `per_page=9999999` → bounded (max 100) | ☐ |

### 3.5 A05: Security Misconfiguration

| Test | Cách test | PASS |
|------|-----------|------|
| APP_DEBUG in production | `APP_ENV=production` + `APP_DEBUG=true` → block | ☐ |
| CORS too permissive | `OPTIONS /api` với origin lạ → kiểm tra allow | ☐ |
| Stack trace lộ | Error response không lộ file/line khi production | ☐ |
| Directory listing | `GET /storage/` → 403 hoặc index | ☐ |
| Sensitive headers | `X-Powered-By`, `Server` → nên ẩn | ☐ |

### 3.6 A06: Vulnerable Components

| Test | Cách test | PASS |
|------|-----------|------|
| Composer audit | `composer audit` → 0 vulnerabilities | ☐ |
| PHP version check | `php siro doctor` → >= 8.2 | ☐ |
| Extension check | `php siro env:check` → all extensions loaded | ☐ |

### 3.7 A07: Identification & Auth Failures

| Test | Cách test | PASS |
|------|-----------|------|
| JWT token expiry | Hết hạn → 401 | ☐ |
| Refresh token reuse | Dùng refresh token cũ → reject | ☐ |
| Session fixation | Đổi session ID sau login | ☐ |
| Brute force | Sai password nhiều lần → rate limit | ☐ |
| Email enumeration | Login fail → "Invalid credentials" chung chung | ☐ |

### 3.8 A08: Data Integrity

| Test | Cách test | PASS |
|------|-----------|------|
| CSRF | POST không CSRF token → 419 hoặc block | ☐ |
| JWT signature | Token với signature sai → reject | ☐ |
| Signed URL | URL hết hạn hoặc sai signature → reject | ☐ |
| Encryption HMAC | Data bị tamper → throw RuntimeException | ☐ |

### 3.9 A09: Logging & Monitoring

| Test | Cách test | PASS |
|------|-----------|------|
| Auth failure log | Sai password → Logger::error ghi IP + Path | ☐ |
| SQL slow query log | Query >100ms → log | ☐ |
| Trace ID mỗi request | Response header `X-Siro-Trace-Id` | ☐ |
| Log sanitization | Password/token trong log → [REDACTED] | ☐ |
| Log retention | `LOG_RETENTION_DAYS=30` → cleanup tự động | ☐ |

### 3.10 A10: SSRF

| Test | Cách test | PASS |
|------|-----------|------|
| HTTP client URL validation | `Http::get('file:///etc/passwd')` → block | ☐ |
| Internal IP access | `Http::get('http://169.254.169.254/')` → block | ☐ |

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
php siro doctor             # → health check
php siro key:generate       # → generate JWT secret
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

### 5d. Debug & Log

```bash
php siro debug:last       # → last request info
php siro log:trace        # → trace list
php siro log:stats        # → log statistics
php siro log:cleanup --dry-run  # → cleanup simulation
php siro log:slow         # → slow queries
php siro rate:status      # → rate limit dashboard
```

### 5e. Server & Deploy

```bash
php siro serve --port=8081 &  # → server start
php siro live --port=9090 &   # → live reload (nếu có)
php siro down --message="Test"  # → maintenance mode
php siro up                     # → restore
php siro config:cache           # → cache config
php siro optimize               # → optimize
```

### 5f. Queue & Schedule

```bash
php siro queue:status     # → queue dashboard
php siro schedule:run     # → run scheduled tasks
```

### 5g. Cleanup sau khi test

```bash
git checkout routes/api.php  # restore routes
# Xoá file đã generate:
rm -f app/Models/Test*.php app/Controllers/Test*.php app/Services/Test*.php \
      app/Repositories/Test*.php app/Resources/Test*.php app/Jobs/Test*.php \
      app/Mails/Test*.php app/Events/Test*.php database/factories/Test*.php \
      tests/Feature/Test*.php
```

---

## 6. ⚡ Performance Benchmark

```bash
# CLI speed (~10 lần)
$times = @(); for ($i=1; $i -le 10; $i++) { $sw=[Diagnostics.Stopwatch]::StartNew(); php siro route:list 2>$null; $sw.Stop(); $times+=$sw.ElapsedMilliseconds }; ($times | Measure-Object -Average).Average
# Kỳ vọng: ~70-80ms average

# API speed (dev server)
php siro serve --port=8081 &
Start-Sleep 3
for ($i=1; $i -le 10; $i++) { $sw=[Diagnostics.Stopwatch]::StartNew(); Invoke-WebRequest http://localhost:8081/health -UseBasicParsing; $sw.Stop(); $sw.ElapsedMilliseconds }
# Kỳ vọng: <100ms (sau warm-up)
```

---

## 7. ✅ Pre-Release Checklist

### Cả 2 repo

- [ ] PHPUnit: **458 tests — 100% pass**
- [ ] PHPStan Level 6: **0 errors**
- [ ] OWASP: **tất cả các mục A01-A10 đã PASS**
- [ ] E2E: **18/18 tests pass**
- [ ] Real-user API CRUD: **tất cả resources hoạt động**
- [ ] Validation (422): **tất cả endpoints trả về chuẩn**
- [ ] 404: **tất cả resources trả về chuẩn**
- [ ] Auth: register, login, duplicate, wrong password
- [ ] Pagination: **meta fields đầy đủ**
- [ ] Filtering: **category, status, price, search**
- [ ] Rate limiting: **hoạt động (không crash)**
- [ ] CLI: **20+ commands tested OK**
- [ ] Performance: **CLI ~77ms, API <100ms**
- [ ] `composer audit` — **0 vulnerabilities**

### SiroPHP-specific

- [ ] LICENSE file exists (MIT)
- [ ] .editorconfig đúng chuẩn
- [ ] .gitattributes đúng chuẩn
- [ ] composer.json version chính xác
- [ ] README badge test count chính xác
- [ ] CHANGELOG đã cập nhật
- [ ] `routes/api.php` không còn reference controller cũ
- [ ] `phpstan.neon` exclude paths chính xác
- [ ] `.env.example` đầy đủ config mẫu
- [ ] Docker: build thử `docker-compose up`
- [ ] Maintenance mode: `php siro down` + `php siro up` OK

### siro-core-specific

- [ ] composer.json version bump
- [ ] CHANGELOG đã cập nhật
- [ ] PHPStan baseline regenerated (nếu thay đổi code)
- [ ] PHPUnit coverage không giảm so với version trước

---

## 8. 🧪 OWASP Automation Script

```bash
# Chạy script OWASP testing nếu có:
# php tests/owasp_test.php

# Các lệnh thủ công để test từng mục:

# A01 - IDOR
curl -X PUT http://localhost:8081/api/users/1 \
  -H "Authorization: Bearer $(php siro api:test POST /api/auth/login email=test@test.com password=pass --raw-token)" \
  -H "Content-Type: application/json" \
  -d '{"name":"hacker"}'

# A03 - SQLi
curl -X GET "http://localhost:8081/api/products?sort=id;DROP TABLE users--"

# A03 - XSS
curl -X POST http://localhost:8081/api/categories \
  -H "Content-Type: application/json" \
  -d '{"name":"<script>alert(1)</script>"}'

# A04 - Rate limit
for ($i=0; $i -lt 100; $i++) { Invoke-WebRequest http://localhost:8081/api/auth/login -Method POST }

# A05 - Debug mode
curl http://localhost:8081/api/nonexistent | Select-String "trace|file|line"

# A07 - JWT none algorithm
curl http://localhost:8081/api/auth/me \
  -H "Authorization: Bearer eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJzdWIiOiIxIn0."
```

---

## 9. 🚨 Quick Recovery

```bash
# Khi PHPUnit fail
php vendor/bin/phpunit --filter=FailingTest  # chạy 1 test
php vendor/bin/phpunit --testsuite=Unit       # chạy 1 suite

# Khi PHPStan fail
php -d memory_limit=512M vendor/bin/phpstan analyse --level=6 --generate-baseline

# Khi routes bị hỏng sau khi test make:crud
git checkout routes/api.php

# Khi database test bị hỏng
rm -f storage/test.db storage/database.sqlite
php siro migrate

# Reset toàn bộ về commit sạch
git stash
git checkout main
```
