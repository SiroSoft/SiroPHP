# Quick Setup Guide - siro-core Repository

## ✅ Files Prepared

1. ✅ `d:\VietVang\siro-core\` - Directory created with all core files
2. ✅ `d:\VietVang\SiroPHP\siro-core-composer-final.json` - Ready to use composer.json

---

## 🚀 Execute These Commands

### Step 1: Replace composer.json

```powershell
# Copy the prepared composer.json
cd d:\VietVang\siro-core
Copy-Item "..\SiroPHP\siro-core-composer-final.json" "composer.json" -Force

# Validate
composer validate
```

Expected: `./composer.json is valid`

---

### Step 2: Initialize Git

```powershell
cd d:\VietVang\siro-core

# Initialize git (if not already done)
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit: Siro Core v0.7.4"

# Rename branch
git branch -M main
```

---

### Step 3: Create GitHub Repository

**Go to:** https://github.com/new

Fill in:
- **Repository name:** `siro-core`
- **Owner:** `SiroSoft`
- **Description:** `Siro API Framework Core Library`
- **Public:** ✓ (check this)
- **Initialize with README:** ✗ (DO NOT check)

Click **"Create repository"**

---

### Step 4: Push to GitHub

After creating the repo, GitHub will show you commands. Run:

```powershell
cd d:\VietVang\siro-core

# Add remote
git remote add origin https://github.com/SiroSoft/siro-core.git

# Push
git push -u origin main

# Tag version
git tag v0.7.4
git push origin v0.7.4
```

---

### Step 5: Verify

Check your repository:
- URL: https://github.com/SiroSoft/siro-core
- Should show all files
- Should have tag v0.7.4

Validate package:
```powershell
cd d:\VietVang\siro-core
composer validate
```

---

## ✅ Done!

After completing these steps, you'll have:
- ✅ siro-core repository on GitHub
- ✅ Git tag v0.7.4
- ✅ Valid composer.json
- ✅ Ready for Packagist submission

---

## Next: Setup siro-api Repository

After siro-core is published, proceed to update SiroPHP repository.

See: `TWO_PACKAGE_SETUP_GUIDE.md` Phase 2
