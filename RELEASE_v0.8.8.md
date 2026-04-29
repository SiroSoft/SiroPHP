# Release v0.8.8 - CLI API Testing Tool

**Release Date:** April 30, 2026

## 🎉 New Feature

### `php siro api:test` - Built-in API Testing CLI

A powerful command-line API testing tool that replaces Postman for quick endpoint testing. Zero dependencies, auto authentication, and request history!

---

## 🚀 Quick Start

### Basic Usage

```bash
# Test GET endpoint
php siro api:test GET /api/users

# Test POST with data
php siro api:test POST /auth/login email=admin@test.com password=123456

# Test with authentication
php siro api:test GET /users --as=admin

# Test with custom headers
php siro api:test GET /api/data --header="X-App-Version: 2.0"

# Test on different port
php siro api:test GET /health --port=8080
```

---

## ✨ Key Features

### 1. Auto Authentication

Login once, token saved automatically for subsequent requests:

```bash
# Login and save token
php siro api:test POST /auth/login \
    email=admin@test.com \
    password=123456 \
    --as=admin

# Token saved! Next requests auto-use it
php siro api:test GET /users --as=admin
php siro api:test POST /users name=John email=john@test.com --as=admin
php siro api:test DELETE /users/1 --as=admin
```

**How it works:**
- Tokens saved to `storage/api-test-auth.json`
- Organized by role (admin, user, etc.)
- Automatically adds `Authorization: Bearer <token>` header
- No need to copy-paste tokens manually!

### 2. Pretty Output

Beautiful colored output with formatted JSON:

```
  POST /auth/login
  Status: 200 OK
  Time:   45.2ms
  Size:   1.2KB

  Response Headers:
    HTTP/1.1 200 OK
    Content-Type: application/json
    X-Siro-Trace-Id: siro_abc123

  Body:
  {
      "success": true,
      "message": "Login successful",
      "data": {
          "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
          "user": {
              "id": 1,
              "name": "Admin",
              "email": "admin@test.com"
          }
      }
  }

  ✓ Token for 'admin' saved.
```

**Features:**
- ✅ Color-coded status codes (green=success, yellow=redirect, red=error)
- ✅ Pretty-printed JSON responses
- ✅ Response headers displayed
- ✅ Request duration in milliseconds
- ✅ Response size in human-readable format (B, KB, MB)

### 3. Request History

Automatically saves last 100 requests for review:

```bash
# View recent history (default: 10 requests)
php siro api:test --history

# View more requests
php siro api:test --history=20
php siro api:test --history=50

# Clear history
php siro api:test --history-clear
```

**History Table:**
```
#  | Time                | Method | Path           | Status | Time   | Size  | As
---|---------------------|--------|----------------|--------|--------|-------|----
1  | 2026-04-30 05:30:15 | POST   | /auth/login    | 200    | 45.2ms | 1.2KB | admin
2  | 2026-04-30 05:30:20 | GET    | /users         | 200    | 12.5ms | 3.4KB | admin
3  | 2026-04-30 05:30:25 | POST   | /users         | 201    | 23.1ms | 456B  | admin
4  | 2026-04-30 05:30:30 | GET    | /users/1       | 200    | 8.3ms  | 234B  | admin
5  | 2026-04-30 05:30:35 | DELETE | /users/1       | 200    | 15.7ms | 123B  | admin

Total: 5 requests
Use --history=N to show more, --history-clear to clear
```

### 4. Multiple Content Types

Support both JSON and form-urlencoded:

```bash
# JSON (default)
php siro api:test POST /users \
    name="John Doe" \
    email=john@test.com

# Form data
php siro api:test POST /upload \
    --form \
    file=@photo.jpg \
    title="My Photo"
```

### 5. Custom Headers

Add any custom headers:

```bash
php siro api:test GET /api/data \
    --header="X-App-Version: 2.0" \
    --header="X-Custom-Header: value" \
    --as=admin
```

---

## 📖 Full Command Reference

### Syntax

```bash
php siro api:test <METHOD> <PATH> [field=value...] [options]
```

### Arguments

- `METHOD` - HTTP method (GET, POST, PUT, PATCH, DELETE)
- `PATH` - API endpoint path (e.g., `/api/users`)
- `field=value` - Request body fields (multiple allowed)

### Options

| Option | Description | Example |
|--------|-------------|---------|
| `--json` | Send as JSON (default) | `--json` |
| `--form` | Send as form-urlencoded | `--form` |
| `--header="X: v"` | Custom header | `--header="X-Version: 2.0"` |
| `--as=<role>` | Auth as role | `--as=admin` |
| `--port=<port>` | Server port (default: 8000) | `--port=8080` |
| `--history` | View request history | `--history` |
| `--history=N` | Show last N requests | `--history=20` |
| `--history-clear` | Clear history | `--history-clear` |

---

## 💡 Real-world Examples

### 1. User Registration & Login Flow

```bash
# Register new user
php siro api:test POST /auth/register \
    name="John Doe" \
    email=john@test.com \
    password=password123

# Login as user
php siro api:test POST /auth/login \
    email=john@test.com \
    password=password123 \
    --as=user

# Get user profile (auto-auth)
php siro api:test GET /profile --as=user

# Update profile
php siro api:test PUT /profile \
    name="John Updated" \
    --as=user
```

### 2. Admin CRUD Operations

```bash
# Login as admin
php siro api:test POST /auth/login \
    email=admin@test.com \
    password=admin123 \
    --as=admin

# List all users
php siro api:test GET /users --as=admin

# Create user
php siro api:test POST /users \
    name="Jane Smith" \
    email=jane@test.com \
    role=user \
    --as=admin

# Get specific user
php siro api:test GET /users/5 --as=admin

# Update user
php siro api:test PUT /users/5 \
    name="Jane Updated" \
    --as=admin

# Delete user
php siro api:test DELETE /users/5 --as=admin
```

### 3. File Upload

```bash
# Upload file (form data)
php siro api:test POST /upload \
    --form \
    file=@/path/to/photo.jpg \
    title="My Photo" \
    description="Test upload" \
    --as=user
```

### 4. API Versioning Test

```bash
# Test with custom version header
php siro api:test GET /api/v2/users \
    --header="X-API-Version: 2.0" \
    --as=admin
```

### 5. Health Check

```bash
# Quick health check
php siro api:test GET /api/health

# On different port
php siro api:test GET /api/health --port=8080
```

### 6. Review Testing Session

```bash
# After testing multiple endpoints
php siro api:test --history

# See last 20 requests
php siro api:test --history=20

# Clear and start fresh
php siro api:test --history-clear
```

---

## 🔧 Technical Details

### Auto Auth Mechanism

**Token Storage:**
```json
// storage/api-test-auth.json
{
    "admin": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Flow:**
```
1. php siro api:test POST /auth/login --as=admin
         ↓
2. Send request, get response
         ↓
3. Extract token from response.data.token or response.token
         ↓
4. Save to storage/api-test-auth.json under "admin" key
         ↓
5. Next request with --as=admin auto-adds Authorization header
```

### Request History

**Storage:**
```json
// storage/api-test-history.json
[
    {
        "time": "2026-04-30 05:30:15",
        "method": "POST",
        "path": "/auth/login",
        "fields": {"email": "admin@test.com"},
        "headers": [],
        "status": 200,
        "duration_ms": 45.2,
        "size_bytes": 1234,
        "as": "admin"
    }
]
```

**Auto-cleanup:** Keeps only last 100 requests

### Implementation

- **Zero dependencies** - Uses PHP built-in cURL
- **No external libraries** - Pure PHP implementation
- **Cross-platform** - Works on Windows, Linux, macOS
- **Fast** - Minimal overhead (~5ms startup time)

---

## 🆚 Comparison with Alternatives

| Feature | api:test | Postman | curl | HTTPie |
|---------|----------|---------|------|--------|
| **Setup Time** | 0 min | 5+ min | 0 min | Install |
| **Dependencies** | None | GUI App | None | Python |
| **Auto Auth** | ✅ Yes | ❌ Manual | ❌ Manual | ❌ Manual |
| **History** | ✅ Built-in | ✅ Cloud | ❌ No | ❌ No |
| **Pretty Output** | ✅ Colored | ✅ GUI | ❌ Plain | ✅ Colored |
| **CLI-native** | ✅ Yes | ❌ No | ✅ Yes | ✅ Yes |
| **Project-specific** | ✅ Yes | ❌ Generic | ❌ Generic | ❌ Generic |
| **Token Management** | ✅ Auto | ⚠️ Env vars | ❌ Manual | ❌ Manual |

**api:test wins on:** Zero setup, auto auth, project integration, history tracking

---

## ✅ Benefits

### For Developers

1. **Faster Testing** - No need to open Postman/browser
2. **Better Workflow** - Stay in terminal, keep context
3. **Auto Auth** - Login once, test everywhere
4. **Quick Iteration** - Test → Fix → Test cycle is faster
5. **Shareable** - Commands can be shared in docs/chat

### For Teams

1. **Consistent Testing** - Everyone uses same tool
2. **Documentation** - Commands serve as living docs
3. **Onboarding** - New devs can test APIs immediately
4. **CI/CD Ready** - Can be used in automated tests

### For Projects

1. **Zero Dependencies** - No package installation needed
2. **No Configuration** - Works out of the box
3. **Lightweight** - Minimal resource usage
4. **Integrated** - Part of SiroPHP framework

---

## 📝 Migration Guide

### From Postman

**Before (Postman):**
1. Open Postman app
2. Create new request
3. Set method and URL
4. Add headers manually
5. Copy-paste JWT token
6. Click Send
7. View response

**After (api:test):**
```bash
php siro api:test GET /users --as=admin
# Done! Token auto-applied, response pretty-printed
```

### From curl

**Before (curl):**
```bash
curl -X GET http://localhost:8000/users \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1Q..." \
  -H "Accept: application/json"
```

**After (api:test):**
```bash
php siro api:test GET /users --as=admin
# Token auto-managed, output formatted beautifully
```

---

## 🎯 Best Practices

### 1. Use Role-based Auth

```bash
# Define roles in your workflow
php siro api:test POST /auth/login email=admin@test.com password=... --as=admin
php siro api:test POST /auth/login email=user@test.com password=... --as=user
php siro api:test POST /auth/login email=moderator@test.com password=... --as=moderator

# Switch between roles easily
php siro api:test GET /admin/dashboard --as=admin
php siro api:test GET /user/profile --as=user
```

### 2. Document API Tests

Create a `TESTING.md` file:

```markdown
# API Testing Commands

## Authentication
php siro api:test POST /auth/login email=admin@test.com password=123 --as=admin

## Users
php siro api:test GET /users --as=admin
php siro api:test POST /users name=John email=john@test.com --as=admin

## Products
php siro api:test GET /products
php siro api:test POST /products name="Widget" price=9.99 --as=admin
```

### 3. Use History for Debugging

```bash
# After encountering an error
php siro api:test --history=20

# Review what was sent, identify issues
# Re-run failed requests with modifications
```

### 4. Test Edge Cases

```bash
# Invalid data
php siro api:test POST /users email=invalid --as=admin

# Missing fields
php siro api:test POST /users --as=admin

# Large payloads
php siro api:test POST /upload --form file=@large-file.zip --as=admin
```

---

## 🐛 Troubleshooting

### Server Not Running

```
Error: Failed to connect to 127.0.0.1 port 8000
Make sure the server is running: php siro serve --port=8000
```

**Solution:**
```bash
php siro serve
# Or on different port
php siro serve --port=8080
php siro api:test GET / --port=8080
```

### Token Not Saved

If token isn't auto-saved, check response structure:

```bash
# Expected structures:
{
    "data": {
        "token": "eyJ0eXAi..."
    }
}

# OR
{
    "token": "eyJ0eXAi..."
}
```

**Solution:** Ensure your login endpoint returns token in one of these formats.

### History Not Working

```bash
# Check if history file exists
ls storage/api-test-history.json

# Clear and retry
php siro api:test --history-clear
php siro api:test GET /
php siro api:test --history
```

---

## 📊 Performance

- **Startup time:** ~5ms
- **Request overhead:** <1ms
- **Memory usage:** ~2MB
- **History storage:** ~50KB (100 requests)
- **Auth storage:** ~1KB (multiple roles)

---

## 🚀 Future Enhancements (Potential)

- [ ] Request chaining (use response from one request in next)
- [ ] Environment variables support
- [ ] Test scripts/assertions
- [ ] Export to Postman format
- [ ] Import from OpenAPI spec
- [ ] Parallel request execution
- [ ] Response validation schemas

---

**Full Changelog:** https://github.com/SiroSoft/siro-core/compare/v0.8.7...v0.8.8
