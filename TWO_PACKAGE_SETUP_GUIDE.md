# Hướng Dẫn Tạo 2 Repositories Cho Packagist

## ✅ Đã Hoàn Thành

1. ✅ Thư mục `d:\VietVang\siro-core` đã được tạo
2. ✅ Files từ `core/` đã được copy sang
3. ✅ composer.json template đã sẵn sàng

---

## 📋 Bước Tiếp Theo (Bạn Cần Làm)

### **Phase 1: Setup siro-core Repository**

#### 1.1 Update composer.json trong siro-core

Mở file `d:\VietVang\siro-core\composer.json` và đảm bảo nội dung là:

```json
{
  "name": "siro/core",
  "description": "Siro API Framework Core Library",
  "type": "library",
  "license": "MIT",
  "keywords": ["framework", "api", "micro-framework", "php"],
  "homepage": "https://github.com/SiroSoft/siro-core",
  "support": {
    "issues": "https://github.com/SiroSoft/siro-core/issues",
    "source": "https://github.com/SiroSoft/siro-core"
  },
  "require": {
    "php": ">=8.2",
    "ext-pdo": "*",
    "ext-json": "*",
    "ext-mbstring": "*"
  },
  "autoload": {
    "psr-4": {
      "Siro\\Core\\": ""
    }
  },
  "minimum-stability": "stable"
}
```

#### 1.2 Initialize Git và Push

```powershell
cd d:\VietVang\siro-core

# Initialize git
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit: Siro Core v0.7.4"

# Rename branch to main
git branch -M main

# Tạo repository trên GitHub trước:
# https://github.com/new?name=siro-core&owner=SiroSoft&description=Siro%20API%20Framework%20Core%20Library&private=0

# Add remote
git remote add origin https://github.com/SiroSoft/siro-core.git

# Push
git push -u origin main

# Tag version
git tag v0.7.4
git push origin v0.7.4
```

#### 1.3 Validate Package

```powershell
cd d:\VietVang\siro-core
composer validate
```

Expected output: `./composer.json is valid`

---

### **Phase 2: Update siro-api Repository (SiroPHP)**

#### 2.1 Update composer.json

Trong `d:\VietVang\SiroPHP\composer.json`, đảm bảo có:

```json
{
  "name": "siro/api",
  "description": "Siro API Framework - Starter Project Template",
  "type": "project",
  "license": "MIT",
  "keywords": ["framework", "api", "starter", "template"],
  "homepage": "https://github.com/SiroSoft/SiroPHP",
  "support": {
    "issues": "https://github.com/SiroSoft/SiroPHP/issues",
    "source": "https://github.com/SiroSoft/SiroPHP"
  },
  "require": {
    "php": ">=8.2",
    "ext-pdo": "*",
    "ext-json": "*",
    "ext-mbstring": "*",
    "siro/core": "^0.7"
  },
  "scripts": {
    "post-create-project-cmd": [
      "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
      "@php siro key:generate || true"
    ]
  },
  "bin": ["siro"],
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    }
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

**Lưu ý quan trọng:**
- ❌ KHÔNG có `"repositories"` section
- ✅ Có dependency `"siro/core": "^0.7"`

#### 2.2 Remove core/ Directory (Optional)

Vì siro/core sẽ được install qua Composer, bạn có thể xóa thư mục `core/` khỏi SiroPHP repo:

```powershell
cd d:\VietVang\SiroPHP

# Option 1: Keep core/ for development but ignore in git
# Add to .gitignore: /core/

# Option 2: Remove core/ completely (recommended after siro-core published)
# Remove-Item -Recurse -Force core\
```

**Recommendation:** Giữ lại `core/` cho local development, nhưng thêm vào `.gitignore`:

```
/core/
```

Sau đó khi user install via `composer create-project`, họ sẽ get `siro/core` từ Packagist vào `vendor/siro/core/`.

#### 2.3 Commit và Push

```powershell
cd d:\VietVang\SiroPHP

# Switch to master branch
git checkout master

# Add changes
git add composer.json .gitignore

# Commit
git commit -m "refactor: setup two-package architecture

- siro/api depends on siro/core ^0.7
- Removed path repository
- Ready for Packagist submission"

# Push
git push origin master

# Tag version
git tag v0.7.4
git push origin v0.7.4
```

---

### **Phase 3: Submit to Packagist**

#### 3.1 Submit siro/core FIRST

1. Go to: https://packagist.org/packages/submit
2. Login (create account if needed)
3. Enter URL: `https://github.com/SiroSoft/siro-core`
4. Click "Check"
5. Verify:
   - Package name: `siro/core`
   - Type: `library`
   - Version: `v0.7.4`
6. Click "Submit"
7. Setup webhook (automatic)

#### 3.2 Submit siro/api SECOND

1. Go to: https://packagist.org/packages/submit
2. Enter URL: `https://github.com/SiroSoft/SiroPHP`
3. Click "Check"
4. Verify:
   - Package name: `siro/api`
   - Type: `project`
   - Dependencies: `siro/core ^0.7`
   - Version: `v0.7.4`
5. Click "Submit"
6. Setup webhook (automatic)

---

### **Phase 4: Test Installation**

```powershell
# Create temp directory
cd $env:TEMP
mkdir siro-test
cd siro-test

# Install via Composer
composer create-project siro/api my-app

# Navigate
cd my-app

# Validate
composer validate

# Generate env
php siro key:generate

# Migrate
php siro migrate

# Serve
php siro serve
```

**Expected:**
- ✅ Composer installs successfully
- ✅ Dependencies resolved (siro/core from Packagist)
- ✅ .env created
- ✅ JWT_SECRET generated
- ✅ Migrations run
- ✅ Server starts on http://localhost:8080

---

## 🎯 Checklist

### siro-core Repository
- [ ] composer.json đúng format
- [ ] Git initialized
- [ ] Files committed
- [ ] Pushed to GitHub
- [ ] Tag v0.7.4 created
- [ ] Submitted to Packagist
- [ ] Packagist shows package as published

### siro-api Repository (SiroPHP)
- [ ] composer.json updated (depends on siro/core ^0.7)
- [ ] No path repository
- [ ] Committed and pushed
- [ ] Tag v0.7.4 created
- [ ] Submitted to Packagist
- [ ] Packagist shows package as published

### Testing
- [ ] `composer create-project siro/api test-app` works
- [ ] All CLI commands work
- [ ] HTTP endpoints respond correctly
- [ ] Tests passing

---

## 📝 Notes

### Why Two Packages?

1. **Reusable Core:** Other projects can use `siro/core` without the starter template
2. **Ecosystem:** Can build additional packages (siro/auth, siro/cache, etc.)
3. **Professional:** Matches Laravel/Symfony architecture
4. **Scalable:** Easier to maintain and extend

### Development Workflow

For local development in SiroPHP:

```json
// In SiroPHP/composer.json (development only)
{
  "repositories": [
    {
      "type": "path",
      "url": "../siro-core",
      "options": {
        "symlink": false
      }
    }
  ]
}
```

**But REMOVE this before committing to GitHub!**

Or use Composer's local path for dev:

```bash
composer config repositories.siro-core path ../siro-core
composer update
```

This keeps `core/` out of the repo but allows local testing.

---

## 🚀 Quick Commands Summary

```powershell
# === SIRO-CORE ===
cd d:\VietVang\siro-core
git init
git add .
git commit -m "Initial commit: Siro Core v0.7.4"
git branch -M main
git remote add origin https://github.com/SiroSoft/siro-core.git
git push -u origin main
git tag v0.7.4
git push origin v0.7.4

# === SIRO-API (SiroPHP) ===
cd d:\VietVang\SiroPHP
git checkout master
git add composer.json
git commit -m "refactor: two-package architecture"
git push origin master
git tag v0.7.4
git push origin v0.7.4

# === TEST ===
cd $env:TEMP
composer create-project siro/api test-app
cd test-app
php siro key:generate
php siro migrate
php siro serve
```

---

## ✅ Success Criteria

You're done when:

1. ✅ Both repos exist on GitHub
2. ✅ Both packages published on Packagist
3. ✅ `composer create-project siro/api` works
4. ✅ Fresh installation runs without errors
5. ✅ All tests pass

---

**Good luck! You've got this! 🚀**
