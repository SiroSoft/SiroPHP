<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$args = $argv;
array_shift($args);

$strict = in_array('--strict', $args, true);
$ci = in_array('--ci', $args, true);
$withProdDoctor = in_array('--with-prod-doctor', $args, true);

$steps = [
    ['name' => 'Composer audit', 'cmd' => 'composer audit --no-interaction'],
    ['name' => 'PHPStan', 'cmd' => 'php vendor/bin/phpstan analyse --no-progress --memory-limit=1G'],
    ['name' => 'PHPUnit', 'cmd' => 'php vendor/bin/phpunit --no-progress'],
];

if ($withProdDoctor) {
    $steps[] = ['name' => 'Doctor --prod', 'cmd' => 'php siro doctor --prod'];
} elseif (!$ci) {
    $steps[] = ['name' => 'Doctor', 'cmd' => 'php siro doctor'];
}

$failures = 0;

// Version-consistency gate: composer.json version == CHANGELOG head == page
// footers == openapi.json, and the installed core engine must satisfy the
// sirosoft/core constraint (skeleton and engine versions may differ, e.g.
// skeleton 1.0.1 on engine 1.0.0).
function versionSatisfiesConstraint(string $version, string $constraint): bool
{
    $constraint = trim($constraint);
    if ($constraint === '' || $constraint === '*') {
        return true;
    }
    if (str_starts_with($constraint, '^')) {
        $min = substr($constraint, 1);
        $parts = explode('.', $min);
        $major = (int) ($parts[0] ?? 0);
        $upper = $major > 0 ? ($major + 1) . '.0.0' : '0.' . (((int) ($parts[1] ?? 0)) + 1) . '.0';
        return version_compare($version, $min, '>=') && version_compare($version, $upper, '<');
    }
    if (str_starts_with($constraint, '~')) {
        $min = substr($constraint, 1);
        $parts = explode('.', $min);
        $upper = $parts[0] . '.' . (((int) ($parts[1] ?? 0)) + 1) . '.0';
        return version_compare($version, $min, '>=') && version_compare($version, $upper, '<');
    }
    if (preg_match('/^(>=|<=|>|<|=|==)?\s*(\d+\.\d+\.\d+)$/', $constraint, $m)) {
        return version_compare($version, $m[2], $m[1] !== '' ? $m[1] : '==');
    }
    return true;
}
fwrite(STDOUT, "\n==> Version gate\n");
$versionGateOk = (function () use ($root): bool {
    $composerFile = $root . '/composer.json';
    $composer = is_file($composerFile) ? json_decode((string) file_get_contents($composerFile), true) : null;
    $appVersion = is_array($composer) ? (string) ($composer['version'] ?? '') : '';
    if (!preg_match('/^\d+\.\d+\.\d+$/', $appVersion)) {
        fwrite(STDERR, "[FAIL] Version gate: malformed composer.json version '{$appVersion}'\n");
        return false;
    }
    $changelog = is_file($root . '/CHANGELOG.md') ? (string) file_get_contents($root . '/CHANGELOG.md') : '';
    if (!preg_match('/^## v(\d+\.\d+\.\d+)/m', $changelog, $cm) || $cm[1] !== $appVersion) {
        fwrite(STDERR, "[FAIL] Version gate: CHANGELOG head '" . ($cm[1] ?? '?') . "' != composer.json '{$appVersion}'\n");
        return false;
    }
    $constraint = is_array($composer) ? (string) ($composer['require']['sirosoft/core'] ?? '') : '';
    $runtimeVersion = null;
    $consoleFile = $root . '/vendor/sirosoft/core/Console.php';
    if (is_file($consoleFile) && preg_match("/const VERSION = '([^']+)'/", (string) file_get_contents($consoleFile), $m)) {
        $runtimeVersion = $m[1];
    }
    if ($runtimeVersion !== null && $constraint !== '' && !versionSatisfiesConstraint($runtimeVersion, $constraint)) {
        fwrite(STDERR, "[FAIL] Version gate: runtime core '{$runtimeVersion}' does not satisfy '{$constraint}' (run composer update)\n");
        return false;
    }
    foreach (['public/index.html' => 'SiroPHP v' . $appVersion, 'public/index-prod.html' => 'v' . $appVersion] as $file => $needle) {
        $path = $root . '/' . $file;
        if (is_file($path) && !str_contains((string) file_get_contents($path), $needle)) {
            fwrite(STDERR, "[FAIL] Version gate: {$file} missing '{$needle}'\n");
            return false;
        }
    }
    $openapiFile = $root . '/public/openapi.json';
    if (is_file($openapiFile)) {
        $openapi = json_decode((string) file_get_contents($openapiFile), true);
        $specVersion = is_array($openapi) ? (string) ($openapi['info']['version'] ?? '') : '';
        if ($specVersion !== $appVersion) {
            fwrite(STDERR, "[FAIL] Version gate: openapi.json '{$specVersion}' != '{$appVersion}'\n");
            return false;
        }
    }
    fwrite(STDOUT, "[OK] Version gate ({$appVersion})\n");
    return true;
})();
if (!$versionGateOk) {
    $failures++;
}

foreach ($steps as $step) {
    $name = $step['name'];
    $cmd = $step['cmd'];
    fwrite(STDOUT, "\n==> {$name}\n");
    passthru($cmd, $code);

    if ($code !== 0) {
        $failures++;
        fwrite(STDERR, "[FAIL] {$name} exited with code {$code}\n");
        if ($strict) {
            exit(1);
        }
    } else {
        fwrite(STDOUT, "[OK] {$name}\n");
    }
}

if ($failures > 0) {
    fwrite(STDERR, "\nRelease check completed with {$failures} failure(s).\n");
    exit(1);
}

fwrite(STDOUT, "\nRelease check passed.\n");
exit(0);
