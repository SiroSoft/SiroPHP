#!/usr/bin/env php
<?php
declare(strict_types=1);

$baselineFile = __DIR__ . '/../storage/benchmark/baseline.json';
$baselineDir = dirname($baselineFile);

if (!is_dir($baselineDir)) {
    mkdir($baselineDir, 0775, true);
}

// Start dev server on port 8080 (matching benchmark/benchmark.php expectation)
$port = 8080;
$docRoot = realpath(__DIR__ . '/../public');
$nul = PHP_OS_FAMILY === 'Windows' ? 'nul' : '/dev/null';

$serverPid = null;
if (PHP_OS_FAMILY === 'Windows') {
    $cmd = sprintf('start /B %s -S 127.0.0.1:%d -t %s > %s 2>&1',
        PHP_BINARY, $port, $docRoot, $nul
    );
    $serverProc = proc_open($cmd, [], $pipes, dirname($docRoot));
    if (is_resource($serverProc)) {
        $status = proc_get_status($serverProc);
        $serverPid = $status['running'] ? $status['pid'] : null;
    }
} else {
    $cmd = sprintf('%s -S 127.0.0.1:%d -t %s > %s 2>&1 & echo $!',
        PHP_BINARY, $port, $docRoot, $nul
    );
    $serverPid = (int) exec($cmd);
}

if (!$serverPid) {
    echo "ERROR: Could not start PHP dev server\n";
    exit(1);
}

// Wait for server to be ready
$maxWait = 15;
$ready = false;
for ($i = 0; $i < $maxWait; $i++) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
    if ($fp) {
        fclose($fp);
        $ready = true;
        break;
    }
    sleep(1);
}

register_shutdown_function(function () use ($serverPid) {
    if (PHP_OS_FAMILY === 'Windows') {
        exec('taskkill /F /PID ' . $serverPid . ' 2>nul');
    } else {
        exec('kill ' . $serverPid . ' 2>/dev/null');
    }
});

if (!$ready) {
    echo "ERROR: Dev server did not start within {$maxWait}s\n";
    exit(1);
}

// Run benchmarks
$benchmarkScript = __DIR__ . '/../benchmark/benchmark.php';
$output = shell_exec(PHP_BINARY . ' ' . escapeshellarg($benchmarkScript) . ' 2>&1');

if ($output === null || $output === '') {
    echo "ERROR: Could not run benchmark\n";
    exit(1);
}

// Parse results: "Avg: 5.234ms"
$current = [];
$endpointName = null;
$lines = explode("\n", $output);
foreach ($lines as $line) {
    if (preg_match('/^Testing:\s+(.+?)\.\.\./', $line, $m)) {
        $endpointName = trim($m[1]);
    }
    if ($endpointName && preg_match('/Avg:\s+([\d.]+)ms/', $line, $m)) {
        $current[$endpointName] = (float) $m[1];
        $endpointName = null;
    }
}

if ($current === []) {
    echo "ERROR: Could not parse benchmark output.\n";
    echo "Raw output:\n{$output}\n";
    exit(1);
}

$baseline = [];
if (file_exists($baselineFile)) {
    $content = file_get_contents($baselineFile);
    $decoded = json_decode(is_string($content) ? $content : '{}', true);
    $baseline = is_array($decoded) ? $decoded : [];
}

$isFirstRun = $baseline === [];
$failed = false;

echo str_repeat('-', 100) . "\n";
echo sprintf("%-45s %-15s %-15s %-10s %s\n", 'Benchmark', 'Baseline (ms)', 'Current (ms)', 'Delta %', 'Status');
echo str_repeat('-', 100) . "\n";

$newBaseline = [];
foreach ($current as $name => $value) {
    $baselineValue = $baseline[$name] ?? $value;
    $newBaseline[$name] = $value;

    if ($isFirstRun) {
        $delta = 0;
        $status = 'BASELINE';
    } else {
        $delta = (($value - $baselineValue) / $baselineValue) * 100;
        if ($delta > 10) {
            $status = 'FAIL';
            $failed = true;
        } elseif ($delta > 5) {
            $status = 'WARN';
        } elseif ($delta < -5) {
            $status = 'FASTER';
        } else {
            $status = 'PASS';
        }
    }

    echo sprintf("%-45s %-15s %-15s %-10.1f %s\n", $name,
        $isFirstRun ? '-' : number_format($baselineValue, 4),
        number_format($value, 4),
        $isFirstRun ? 0 : $delta,
        $status
    );
}

echo str_repeat('-', 100) . "\n";

if ($isFirstRun) {
    file_put_contents($baselineFile, json_encode($newBaseline, JSON_PRETTY_PRINT));
    echo "Baseline created: {$baselineFile}\n";
} elseif (!$failed) {
    $smoothed = [];
    foreach ($newBaseline as $name => $value) {
        $old = $baseline[$name] ?? $value;
        $smoothed[$name] = $old * 0.7 + $value * 0.3;
    }
    file_put_contents($baselineFile, json_encode($smoothed, JSON_PRETTY_PRINT));
    echo "Baseline updated (smoothed)\n";
}

if ($failed) {
    echo "PERFORMANCE REGRESSION DETECTED\n";
    exit(1);
}

if (!$isFirstRun) {
    echo "All benchmarks within tolerance\n";
}
exit(0);
