#!/usr/bin/env php
<?php

$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';

$phpunit = $basePath . '/vendor/bin/phpunit';
if (!is_file($phpunit)) {
    echo "\033[31mPHPUnit not found. Run 'composer install' first.\033[0m\n";
    exit(1);
}

$args = $argv;
array_shift($args);
$all = empty($args);
$opt = '';
if ($all) {
    $opt = '';
} elseif (in_array('--unit', $args)) {
    $opt = '--testsuite=Unit';
} elseif (in_array('--integration', $args)) {
    $opt = '--testsuite=Integration';
} elseif (in_array('--feature', $args)) {
    $opt = '--testsuite=Feature';
}

passthru("\"{$phpunit}\" --no-progress {$opt} 2>&1", $exitCode);
exit($exitCode);
