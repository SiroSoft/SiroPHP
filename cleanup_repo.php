#!/usr/bin/env php
<?php

/**
 * Cleanup Script for SiroPHP Repository
 * 
 * This script removes unnecessary files from git tracking while keeping them locally.
 * Run this script from the SiroPHP root directory.
 * 
 * Usage: php cleanup_repo.php [--dry-run]
 */

declare(strict_types=1);

// Files to remove from git tracking
$filesToRemove = [
    // Generated OpenAPI specs (duplicates)
    'openapi-auth.json',
    'openapi_test.json',
    
    // Test databases
    'storage/test.db',
    'storage/test_round3.db',
    
    // API test history (runtime data)
    'storage/api-test-history.json',
    
    // Rate limit cache files
    'storage/rate_limit/30ff2cff9fb616d98c0232be9f98b9fa8a59a701.json',
    'storage/rate_limit/4840fc0b86d1138549d3899d2ac6478e7feb0542.json',
    'storage/rate_limit/7e69e65f143119d22229c316c3e9b15a21837ace.json',
    'storage/rate_limit/9c6a0a529b87492d8c003baf54484d690d7b6386.json',
    'storage/rate_limit/9efa907a6241a8937a60e41252f92e44c492b4d8.json',
    'storage/rate_limit/f0d68d407dd4ee3bf65306980c26d08177d13157.json',
    
    // Duplicate Postman collection in public
    'public/postman_collection.json',
];

$dryRun = in_array('--dry-run', $argv, true);

echo "===========================================\n";
echo "SiroPHP Repository Cleanup Script\n";
echo "===========================================\n\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No changes will be made\n\n";
}

$baseDir = __DIR__;
$removedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($filesToRemove as $file) {
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . $file;
    
    // Check if file exists locally
    if (!file_exists($fullPath)) {
        echo "⚠️  SKIP: {$file} (file does not exist locally)\n";
        $skippedCount++;
        continue;
    }
    
    // Check if file is tracked in git
    $gitCheckCommand = "git ls-files \"{$file}\"";
    exec($gitCheckCommand, $output, $returnCode);
    
    if ($returnCode !== 0 || empty($output)) {
        echo "⚠️  SKIP: {$file} (not tracked in git)\n";
        $skippedCount++;
        continue;
    }
    
    if ($dryRun) {
        echo "✅ WOULD REMOVE: {$file}\n";
        $removedCount++;
    } else {
        // Remove from git tracking (keep local file)
        $command = "git rm --cached \"{$file}\"";
        exec($command, $gitOutput, $gitReturnCode);
        
        if ($gitReturnCode === 0) {
            echo "✅ REMOVED: {$file}\n";
            $removedCount++;
        } else {
            echo "❌ ERROR: Failed to remove {$file}\n";
            $errorCount++;
        }
    }
}

echo "\n===========================================\n";
echo "Summary:\n";
echo "===========================================\n";
echo "Files removed: {$removedCount}\n";
echo "Files skipped: {$skippedCount}\n";
echo "Errors: {$errorCount}\n";
echo "===========================================\n\n";

if (!$dryRun && $removedCount > 0) {
    echo "📝 Next steps:\n";
    echo "1. Review changes: git status\n";
    echo "2. Commit changes: git commit -m \"Remove unnecessary files from repository\"\n";
    echo "3. Verify .gitignore is working: git check-ignore -v <filename>\n\n";
    
    // Check if api-test-history.json needs to be added to .gitignore
    $gitignorePath = $baseDir . DIRECTORY_SEPARATOR . '.gitignore';
    $gitignoreContent = file_get_contents($gitignorePath);
    
    if (strpos($gitignoreContent, 'api-test-history.json') === false) {
        echo "⚠️  WARNING: storage/api-test-history.json is not in .gitignore\n";
        echo "   Adding it now...\n";
        
        $newContent = rtrim($gitignoreContent) . "\n\n# API test history (runtime data)\n/storage/api-test-history.json\n";
        file_put_contents($gitignorePath, $newContent);
        echo "✅ Updated .gitignore\n\n";
    }
}

if ($dryRun) {
    echo "💡 To execute the cleanup, run: php cleanup_repo.php\n\n";
}

exit($errorCount > 0 ? 1 : 0);
