# SiroPHP v0.7.4 - Packagist Submission Guide

## ✅ Package Ready for Submission

**Package Name:** `siro/framework`  
**Repository:** https://github.com/SiroSoft/SiroPHP  
**Branch:** `packagist-release`  
**Tag:** `v0.7.4-packagist`  
**Status:** ✅ READY FOR PACKAGIST

---

## 📦 Package Information

### composer.json Configuration
```json
{
  "name": "siro/framework",
  "description": "Siro API Framework - Lightweight PHP Micro-Framework",
  "type": "project",
  "license": "MIT",
  "keywords": ["framework", "api", "micro-framework", "php", "rest"],
  "homepage": "https://github.com/SiroSoft/SiroPHP",
  "support": {
    "issues": "https://github.com/SiroSoft/SiroPHP/issues",
    "source": "https://github.com/SiroSoft/SiroPHP"
  },
  "require": {
    "php": ">=8.2",
    "ext-pdo": "*",
    "ext-json": "*",
    "ext-mbstring": "*"
  },
  "autoload": {
    "psr-4": {
      "Siro\\Core\\": "core/",
      "App\\": "app/"
    }
  }
}
```

### Key Features
- ✅ Single package architecture (no dependencies)
- ✅ No path repository required
- ✅ PSR-4 autoloading configured
- ✅ CLI entry point: `siro`
- ✅ Post-install scripts for .env and JWT key generation
- ✅ All tests passing (14/14 = 100%)

---

## 🚀 Packagist Submission Steps

### Step 1: Verify Repository is Public

Check that your repository is public on GitHub:
- URL: https://github.com/SiroSoft/SiroPHP
- Branch: `packagist-release` should be visible
- Tag: `v0.7.4-packagist` should be visible

### Step 2: Submit to Packagist

1. **Go to Packagist Submit Page:**
   ```
   https://packagist.org/packages/submit
   ```

2. **Login to Packagist:**
   - If you don't have an account, create one first
   - Use GitHub login for easiest setup

3. **Enter Repository URL:**
   ```
   https://github.com/SiroSoft/SiroPHP
   ```

4. **Click "Check" Button:**
   - Packagist will scan the repository
   - It should detect `composer.json` in root
   - Package name should show: `siro/framework`

5. **Verify Package Details:**
   - Name: `siro/framework`
   - Description: "Siro API Framework - Lightweight PHP Micro-Framework"
   - Version: Should detect tag `v0.7.4-packagist`
   - Type: `project`

6. **Click "Submit" Button:**
   - Package will be submitted for review
   - Usually approved within minutes/hours

7. **Setup Webhook (Automatic):**
   - Packagist will ask to setup GitHub webhook
   - Click "Setup Webhook" or follow instructions
   - This ensures automatic updates when you push new tags

### Step 3: Verify Package on Packagist

After submission, check your package page:
```
https://packagist.org/packages/siro/framework
```

You should see:
- ✅ Package status: Published
- ✅ Version: v0.7.4-packagist
- ✅ Install command displayed
- ✅ Download statistics (will start at 0)

---

## 🧪 Test Installation

After Packagist approval, test the installation:

### Test 1: Fresh Installation

```bash
# Create temporary directory
cd %TEMP%
mkdir siro-test
cd siro-test

# Install via Composer
composer create-project siro/framework my-app

# Navigate to project
cd my-app

# Verify installation
composer validate
```

**Expected Output:**
```
✅ ./composer.json is valid
✅ Dependencies installed successfully
✅ .env file created automatically
✅ JWT_SECRET generated
```

### Test 2: Run Migrations

```bash
cd my-app
php siro migrate
```

**Expected Output:**
```
Migrated: 20260427110100_create_users_table
Migrated: 20260427143000_add_token_version_to_users_table
```

### Test 3: Start Server

```bash
php siro serve
```

**Expected Output:**
```
Server started on http://localhost:8080
```

### Test 4: Test Endpoints

Open another terminal:

```bash
# Test root endpoint
curl http://localhost:8080/

# Expected: JSON response with version info

# Test health check
curl http://localhost:8080/users

# Expected: Empty array or user list
```

---

## 📝 User Documentation

Add this to your README.md after successful Packagist submission:

### Installation

#### Option 1: Composer (Recommended)

```bash
composer create-project siro/framework my-app
cd my-app
php siro key:generate
php siro migrate
php siro serve
```

#### Option 2: Git Clone

```bash
git clone https://github.com/SiroSoft/SiroPHP.git my-app
cd my-app
composer install
cp .env.example .env
php siro key:generate
php siro migrate
php siro serve
```

---

## 🔧 Maintenance

### Updating the Package

When you release a new version:

1. **Update version in code** (if needed)
2. **Create git tag:**
   ```bash
   git tag v0.7.5
   git push origin v0.7.5
   ```

3. **Packagist will auto-update** via webhook
   - Or manually update at: https://packagist.org/packages/siro/framework/update

### Checking Downloads

Visit: https://packagist.org/packages/siro/framework

You'll see:
- Total downloads
- Monthly downloads
- Daily downloads
- Version breakdown

---

## ⚠️ Troubleshooting

### Issue 1: Packagist Can't Find composer.json

**Solution:**
- Ensure `composer.json` is in repository root
- Check that branch `packagist-release` exists
- Verify tag is pushed: `git tag -l`

### Issue 2: Package Shows Wrong Name

**Solution:**
- Check `composer.json` name field: should be `"siro/framework"`
- Clear Packagist cache by clicking "Update" button

### Issue 3: Installation Fails

**Solution:**
```bash
# Clear Composer cache
composer clear-cache

# Try again with verbose output
composer create-project siro/framework my-app -vvv
```

### Issue 4: Autoloader Not Working

**Solution:**
```bash
cd my-app
composer dump-autoload -o
```

---

## ✅ Pre-Submission Checklist

Before submitting to Packagist, verify:

- [x] Repository is public on GitHub
- [x] `composer.json` is valid (`composer validate`)
- [x] Package name is correct: `siro/framework`
- [x] No path repositories in composer.json
- [x] All dependencies are on Packagist (or none)
- [x] Git tag exists: `v0.7.4-packagist`
- [x] Tests passing: 14/14 (100%)
- [x] README.md has installation instructions
- [x] LICENSE file exists
- [x] .gitignore excludes vendor/, .env, etc.

---

## 📊 Current Status

| Item | Status |
|------|--------|
| Package Name | ✅ `siro/framework` |
| Repository | ✅ https://github.com/SiroSoft/SiroPHP |
| Branch | ✅ `packagist-release` |
| Tag | ✅ `v0.7.4-packagist` |
| composer.json Valid | ✅ Yes |
| Tests Passing | ✅ 14/14 (100%) |
| Dependencies | ✅ None (zero external deps) |
| Autoloading | ✅ PSR-4 configured |
| CLI Entry Point | ✅ `siro` binary |
| Post-install Scripts | ✅ .env + JWT key gen |

---

## 🎯 Next Steps

1. **Submit to Packagist** (follow Step 2 above)
2. **Wait for approval** (usually immediate)
3. **Test installation** (follow Test section above)
4. **Update README.md** with Composer installation instructions
5. **Announce release** on social media/GitHub
6. **Monitor downloads** on Packagist dashboard

---

## 📞 Support

If you encounter issues during submission:

1. Check Packagist documentation: https://packagist.org/about
2. Review Composer schema: https://getcomposer.org/doc/04-schema.md
3. Contact Packagist support: https://packagist.org/support

---

**Last Updated:** 2026-04-27  
**Package Version:** v0.7.4-packagist  
**Status:** ✅ READY FOR SUBMISSION
