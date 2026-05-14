#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$basePath = dirname(__DIR__);
$docsDir = $basePath . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'api';

if (!is_dir($docsDir)) {
    mkdir($docsDir, 0775, true);
}

$phpdocBin = $basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpdoc';

if (is_file($phpdocBin)) {
    echo "Generating API documentation with phpDocumentor...\n";
    passthru("php \"$phpdocBin\" --config=\"" . $basePath . '/phpdoc.dist.xml" 2>&1', $exitCode);
    if ($exitCode === 0) {
        echo "API documentation generated: $docsDir\n";
    } else {
        fwrite(STDERR, "phpDocumentor failed with exit code $exitCode\n");
        exit($exitCode);
    }
} else {
    echo "phpDocumentor not installed. Run: composer require --dev phpdocumentor/phpdoc\n";
}

exit(0);
