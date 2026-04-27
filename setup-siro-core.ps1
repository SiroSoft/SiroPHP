# Script to setup siro-core repository
# Run this in PowerShell

Write-Host "=== Setting up siro-core repository ===" -ForegroundColor Cyan
Write-Host ""

$siroCorePath = "d:\VietVang\siro-core"

# Check if directory exists
if (-not (Test-Path $siroCorePath)) {
    Write-Host "ERROR: siro-core directory not found!" -ForegroundColor Red
    exit 1
}

# Navigate to siro-core
Set-Location $siroCorePath

Write-Host "Step 1: Creating composer.json..." -ForegroundColor Yellow

# Create proper composer.json
$composerJson = @{
    name = "siro/core"
    description = "Siro API Framework Core Library"
    type = "library"
    license = "MIT"
    keywords = @("framework", "api", "micro-framework", "php")
    homepage = "https://github.com/SiroSoft/siro-core"
    support = @{
        issues = "https://github.com/SiroSoft/siro-core/issues"
        source = "https://github.com/SiroSoft/siro-core"
    }
    require = @{
        php = ">=8.2"
        "ext-pdo" = "*"
        "ext-json" = "*"
        "ext-mbstring" = "*"
    }
    autoload = @{
        "psr-4" = @{
            "Siro\\Core\\" = ""
        }
    }
    "minimum-stability" = "stable"
}

# Convert to JSON and save
$composerJson | ConvertTo-Json -Depth 10 | Set-Content "composer.json" -Encoding UTF8

Write-Host "✓ composer.json created" -ForegroundColor Green
Write-Host ""

Write-Host "Step 2: Validating composer.json..." -ForegroundColor Yellow
composer validate

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ composer.json is valid" -ForegroundColor Green
} else {
    Write-Host "✗ composer.json validation failed" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Step 3: Initializing Git repository..." -ForegroundColor Yellow

# Check if git is already initialized
if (-not (Test-Path ".git")) {
    git init
    Write-Host "✓ Git initialized" -ForegroundColor Green
} else {
    Write-Host "✓ Git already initialized" -ForegroundColor Green
}

Write-Host ""
Write-Host "Step 4: Adding files to Git..." -ForegroundColor Yellow
git add .
Write-Host "✓ Files added" -ForegroundColor Green

Write-Host ""
Write-Host "Step 5: Committing..." -ForegroundColor Yellow
git commit -m "Initial commit: Siro Core v0.7.4"
Write-Host "✓ Committed" -ForegroundColor Green

Write-Host ""
Write-Host "Step 6: Renaming branch to main..." -ForegroundColor Yellow
git branch -M main
Write-Host "✓ Branch renamed to main" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "NEXT STEPS:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Create GitHub repository:" -ForegroundColor Yellow
Write-Host '   Go to: https://github.com/new?name=siro-core&owner=SiroSoft' -ForegroundColor White
Write-Host ""
Write-Host "2. After creating repo, run:" -ForegroundColor Yellow
Write-Host "   git remote add origin https://github.com/SiroSoft/siro-core.git" -ForegroundColor White
Write-Host "   git push -u origin main" -ForegroundColor White
Write-Host "   git tag v0.7.4" -ForegroundColor White
Write-Host "   git push origin v0.7.4" -ForegroundColor White
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
