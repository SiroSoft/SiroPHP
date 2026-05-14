#!/usr/bin/env php
<?php
declare(strict_types=1);

$coreScript = __DIR__ . '/../vendor/sirosoft/core/scripts/health-check.php';
if (!is_file($coreScript)) {
    fwrite(STDERR, "Error: siro-core health check script not found at $coreScript\n");
    exit(1);
}

passthru(escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($coreScript) . ' ' . escapeshellarg(dirname(__DIR__)) . ' ' . escapeshellarg($argv[1] ?? 'cli'), $exitCode);
exit($exitCode);
