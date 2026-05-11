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
