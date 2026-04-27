# SiroPHP Repository Split Guide

## Current State
- Single monorepo: `SiroSoft/SiroPHP`
- Contains both `siro/core` (library) and `siro/api` (project template)

## Target State
- Two separate repositories:
  1. `SiroSoft/siro-core` - Framework library
  2. `SiroSoft/siro-api` - Starter project template

---

## Step 1: Create siro-core Repository

### Files to Include:
```
core/
├── App.php
├── Auth/
├── Cache/
├── Cache.php
├── Commands/
├── Console.php
├── DB/
├── DB.php
├── Database.php
├── Env.php
├── Logger.php
├── Request.php
├── Resource.php
├── Response.php
├── Route.php
├── Router.php
├── Validator.php
└── composer.json
```

### Repository Setup:
```bash
# Create new repo
mkdir siro-core
cd siro-core
git init

# Copy core files
cp -r ../SiroPHP/core/* .

# Initialize with proper structure
git add .
git commit -m "Initial commit: Siro Core Framework v0.7.4"
git tag v0.7.4
git remote add origin https://github.com/SiroSoft/siro-core.git
git push -u origin master
git push origin v0.7.4
```

### composer.json for siro-core:
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

---

## Step 2: Create siro-api Repository

### Files to Include:
```
app/
config/
database/
public/
routes/
storage/
tests/
.env.example
composer.json
README.md
siro (CLI entry point)
```

### Repository Setup:
```bash
# Create new repo
mkdir siro-api
cd siro-api
git init

# Copy project files (excluding core/)
rsync -av --exclude='core/' --exclude='.git' ../SiroPHP/ .

# Remove local path repository from composer.json
# (will be handled in next step)

git add .
git commit -m "Initial commit: Siro API Framework v0.7.4"
git tag v0.7.4
git remote add origin https://github.com/SiroSoft/siro-api.git
git push -u origin master
git push origin v0.7.4
```

### composer.json for siro-api (AFTER publishing siro-core):
```json
{
  "name": "siro/api",
  "description": "Siro API Framework - Starter Project Template",
  "type": "project",
  "license": "MIT",
  "keywords": ["framework", "api", "starter", "template"],
  "homepage": "https://github.com/SiroSoft/siro-api",
  "support": {
    "issues": "https://github.com/SiroSoft/siro-api/issues",
    "source": "https://github.com/SiroSoft/siro-api"
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

---

## Step 3: Publish to Packagist

### 3.1 Publish siro-core first
1. Go to https://packagist.org/packages/submit
2. Enter: `https://github.com/SiroSoft/siro-core`
3. Click "Check"
4. Click "Submit"

### 3.2 Publish siro-api second
1. Go to https://packagist.org/packages/submit
2. Enter: `https://github.com/SiroSoft/siro-api`
3. Click "Check"
4. Click "Submit"

---

## Step 4: Verify Installation

```bash
# Test clean installation
composer create-project siro/api test-app
cd test-app
php siro key:generate
php siro migrate
php siro serve
```

---

## Alternative: Keep Monorepo (Not Recommended)

If you want to keep a single repository, you must:

1. **Remove the local path repository** from root composer.json
2. **Publish only siro/api** to Packagist
3. Users will get siro/core as a dependency automatically

### Modified root composer.json (for monorepo):
```json
{
  "name": "siro/api",
  "description": "Siro API Framework Starter Project",
  "type": "project",
  "license": "MIT",
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
  }
}
```

**Problem**: This won't work until siro/core is published to Packagist first!

---

## Recommended Approach

**Split into two repositories** as described above. This provides:
- ✅ Clean separation of concerns
- ✅ Independent versioning
- ✅ Proper Packagist integration
- ✅ Clear dependency management
- ✅ Follows Laravel-style package structure

---

## Quick Execution Plan

```bash
# 1. Backup current repo
cp -r SiroPHP SiroPHP-backup

# 2. Create siro-core repo
./scripts/split-core.sh

# 3. Create siro-api repo  
./scripts/split-api.sh

# 4. Submit both to Packagist
# (manual step via web interface)

# 5. Test installation
composer create-project siro/api test-installation
```
