<?php

declare(strict_types=1);

/**
 * Mapping guard: every registered route must exist in public/openapi.json.
 *
 * Usage: php scripts/check-mapping.php [--json]
 * Exit 1 with the missing list when drift is detected (CI gate for REQ-4).
 */

$root = dirname(__DIR__);

$routesFile = $root . '/routes/api.php';
$openapiFile = $root . '/public/openapi.json';

if (!is_file($openapiFile)) {
    fwrite(STDERR, "[FAIL] openapi.json not found. Regenerate: php siro make:openapi --force\n");
    exit(1);
}

$openapi = json_decode((string) file_get_contents($openapiFile), true);
$paths = is_array($openapi['paths'] ?? null) ? $openapi['paths'] : [];

$src = (string) file_get_contents($routesFile);
$found = [];

// Routes declared after group('/api' inherit the /api prefix.
$groupPos = strpos($src, "group('/api'");
$inGroup = static function (int $offset) use ($groupPos): bool {
    return $groupPos !== false && $offset > $groupPos;
};

// $router->get|post|put|patch|delete('path', ...)
if (preg_match_all('/\$router->(get|post|put|patch|delete)\(\s*\'([^\']+)\'/', $src, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
    foreach ($m as $match) {
        $method = strtoupper($match[1][0]);
        $path = $match[2][0];
        if ($inGroup((int) $match[0][1]) && !str_starts_with($path, '/api')) {
            $path = '/api' . $path;
        }
        $found[] = [$method, $path];
    }
}
// $router->resource('name', ...) expands to 5 REST routes
if (preg_match_all('/\$router->resource\(\s*\'([^\']+)\'/', $src, $m)) {
    foreach ($m[1] as $name) {
        $found[] = ['GET', '/api/' . $name];
        $found[] = ['POST', '/api/' . $name];
        $found[] = ['GET', '/api/' . $name . '/{id}'];
        $found[] = ['PUT', '/api/' . $name . '/{id}'];
        $found[] = ['DELETE', '/api/' . $name . '/{id}'];
    }
}

$missing = [];
foreach ($found as [$method, $path]) {
    $lm = strtolower($method);
    if (!isset($paths[$path]) || !isset($paths[$path][$lm])) {
        $missing[] = "{$method} {$path}";
    }
}

$asJson = in_array('--json', $argv ?? [], true);
if ($asJson) {
    echo json_encode(['total_routes' => count($found), 'missing' => array_values(array_unique($missing))], JSON_PRETTY_PRINT) . PHP_EOL;
}

if ($missing !== []) {
    fwrite(STDERR, "[FAIL] " . count($missing) . " route(s) missing from openapi.json:\n");
    foreach (array_unique($missing) as $line) {
        fwrite(STDERR, "  - {$line}\n");
    }
    fwrite(STDERR, "Regenerate: php siro make:openapi --force\n");
    exit(1);
}

fwrite(STDOUT, "[OK] Mapping guard: " . count($found) . " routes covered by openapi.json\n");
exit(0);
