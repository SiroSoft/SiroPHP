# Demo thực tế: 500 Error → why → replay → fix → replay verify

**Scenario:** User gọi `GET /api/product` → server crash (500)  
**Auth:** Required (JWT token)

---

## Step 1: Request gây lỗi 500

```bash
curl -X GET http://localhost:8080/api/product -H "Authorization: Bearer eyJ..."
```

Response:
```json
HTTP/1.1 500 Internal Server Error
{"success":false,"message":"RuntimeException: LOI 500 - simulated server crash for demo"}
```

---

## Step 2: `siro why` — xem trace lỗi

```bash
php siro why
```

```
Request
────────────────────────────────────────────────────────
Route:      GET /api/product
Status:     ✗ 500
Duration:   4ms
Trace ID:   80965aee18ac3b47fbd7e1811677dbbd
────────────────────────────────────────────────────────

Exception
  └ RuntimeException: LOI 500 - simulated server crash for demo

Possible Cause
  • Unhandled exception in controller or service layer
  • Review exception details above for root cause

Suggested Fix
  ▸ php siro replay ... --force to reproduce locally
  ▸ php siro replay ... --edit to test potential fixes
  ▸ php siro replay ... --test to lock in the fix

Replay
  [r]  php siro replay ... --force
  [e]  php siro replay ... --edit
  [d]  php siro replay ... --diff
  [t]  php siro replay ... --test
```

---

## Step 3: `siro api:why GET /api/product` — trace endpoint cụ thể

```bash
php siro api:why GET /api/product
```

Output giống hệt `siro why` — lookup theo method + path, hiển thị cùng trace.

---

## Step 4: `siro replay` — tái hiện lỗi 500

```bash
php siro replay 80965aee18ac3b47fbd7e1811677dbbd --force
```

```
🔄 Replaying GET /api/product...
========================================
Headers:
  User-Agent: curl/8.19.0
  Accept: */*
  Authorization: [REDACTED]
----------------------------------------
Status: 500
Response:
  {"success":false,"message":"RuntimeException: LOI 500 ..."}
```

> **Auth tự động pass** — token valid hoặc auto-refresh. Lỗi 500 được tái hiện chính xác.

---

## Step 5: Fix code

Xoá `throw new RuntimeException(...)` khỏi `ProductController::index()`.

```diff
- throw new \RuntimeException('LOI 500 - simulated server crash for demo');
  $perPage = min($request->queryInt('per_page', 20), 100);
```

---

## Step 6: `siro replay` — xác nhận fix thành công

```bash
php siro replay 80965aee18ac3b47fbd7e1811677dbbd --force
```

```
🔄 Replaying GET /api/product...
----------------------------------------
Status: 200
Response:
  {
      "success": true,
      "message": "Products list",
      "data": [],
      "meta": { "page": 1, "per_page": 20, "total": 0 }
  }
```

> **✅ Bug fixed!** Cùng trace ID, cùng request — giờ trả về 200 OK.

---

## Kết luận

| Bước | Command | Trạng thái |
|------|---------|-----------|
| 1 | Gọi API | 500 |
| 2 | `siro why` | Trace + Exception + Cause + Fix |
| 3 | `siro api:why` | Cùng format, lookup theo endpoint |
| 4 | `siro replay` | ✅ Tái hiện 500, auth tự động |
| 5 | Fix code | Xoá throw |
| 6 | `siro replay` | **✅ 200 OK — fix verified** |

**Workflow:** `why` → `replay` → fix → `replay` = liền mạch, không config, không login lại.
