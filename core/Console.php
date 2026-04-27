<?php

declare(strict_types=1);

namespace Siro\Core;

final class Console
{
    public function __construct(private readonly string $basePath)
    {
    }

    /** @param array<int, string> $argv */
    public function run(array $argv): int
    {
        $command = $argv[1] ?? '';

        if ($command === '' || in_array($command, ['-h', '--help', 'help'], true)) {
            $this->printHelp();
            return 0;
        }

        if ($command !== 'make:api') {
            $this->write('Unknown command: ' . $command);
            $this->printHelp();
            return 1;
        }

        $name = $argv[2] ?? '';
        if ($name === '') {
            $this->write('Resource name is required. Example: php siro make:api users');
            return 1;
        }

        return $this->makeApi($name);
    }

    private function makeApi(string $resource): int
    {
        $resource = trim(strtolower($resource));
        if ($resource === '') {
            $this->write('Invalid resource name.');
            return 1;
        }

        $model = $this->studly($this->singular($resource));
        $controllerClass = $model . 'Controller';
        $controllerPath = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $controllerClass . '.php';
        $routeFile = $this->basePath . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'api.php';

        if (is_file($controllerPath) && !$this->confirmOverwrite($controllerPath)) {
            $this->write('Skipped controller generation.');
        } else {
            $controllerCode = $this->controllerTemplate($controllerClass, $resource, $model);
            file_put_contents($controllerPath, $controllerCode);
            $this->write('Generated: app/Controllers/' . $controllerClass . '.php');
        }

        $routesSource = is_file($routeFile) ? (string) file_get_contents($routeFile) : "<?php\n\n";
        $routeMarker = "'/$resource'";
        if (str_contains($routesSource, $routeMarker)) {
            $this->write('Routes already exist in routes/api.php. Skipped route generation.');
            return 0;
        }

        $routeBlock = $this->routeTemplate($resource, $controllerClass);
        $newContent = rtrim($routesSource) . "\n\n" . $routeBlock . "\n";
        file_put_contents($routeFile, $newContent);
        $this->write('Updated: routes/api.php');

        return 0;
    }

    private function confirmOverwrite(string $path): bool
    {
        $relative = str_replace($this->basePath . DIRECTORY_SEPARATOR, '', $path);
        $answer = $this->ask('File ' . $relative . ' already exists. Overwrite? (y/N): ');
        return in_array(strtolower($answer), ['y', 'yes'], true);
    }

    private function ask(string $question): string
    {
        if (function_exists('readline')) {
            $value = readline($question);
            return is_string($value) ? trim($value) : '';
        }

        echo $question;
        $value = fgets(STDIN);
        return is_string($value) ? trim($value) : '';
    }

    private function write(string $line): void
    {
        echo $line . PHP_EOL;
    }

    private function printHelp(): void
    {
        $this->write('Siro Console');
        $this->write('Usage:');
        $this->write('  php siro make:api users');
    }

    private function singular(string $value): string
    {
        if (str_ends_with($value, 'ies')) {
            return substr($value, 0, -3) . 'y';
        }

        if (str_ends_with($value, 's') && strlen($value) > 1) {
            return substr($value, 0, -1);
        }

        return $value;
    }

    private function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }

    private function controllerTemplate(string $class, string $resource, string $model): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Controllers;

use Siro\Core\Database;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Validator;

final class {$class}
{
    public function index(Request $request): Response
    {
        $perPage = (int) $request->query('per_page', 20);
        $result = Database::table('{$resource}')
            ->orderBy('id', 'desc')
            ->paginate($perPage > 0 ? $perPage : 20);

        return Response::success(
            $result['data'],
            '{$model} list fetched successfully',
            200,
            $result['meta']
        );
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id', 0);
        if ($id <= 0) {
            return Response::error('Invalid id', 422, ['id' => ['Id must be a positive integer']]);
        }

        $item = Database::table('{$resource}')
            ->where('id', $id)
            ->first();

        if ($item === null) {
            return Response::error('{$model} not found', 404);
        }

        return Response::success($item, '{$model} fetched successfully');
    }

    public function store(Request $request): Response
    {
        $errors = Validator::make($request->body(), [
            'name' => 'required|min:3|max:120',
        ]);

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        $id = Database::table('{$resource}')->insert([
            'name' => (string) $request->input('name'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return Response::created(['id' => $id], '{$model} created successfully');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id', 0);
        if ($id <= 0) {
            return Response::error('Invalid id', 422, ['id' => ['Id must be a positive integer']]);
        }

        $errors = Validator::make($request->body(), [
            'name' => 'min:3|max:120',
        ]);

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        $affected = Database::table('{$resource}')
            ->where('id', $id)
            ->update([
                'name' => $request->input('name'),
            ]);

        if ($affected === 0) {
            return Response::error('{$model} not found', 404);
        }

        return Response::success(null, '{$model} updated successfully');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id', 0);
        if ($id <= 0) {
            return Response::error('Invalid id', 422, ['id' => ['Id must be a positive integer']]);
        }

        $affected = Database::table('{$resource}')
            ->where('id', $id)
            ->delete();

        if ($affected === 0) {
            return Response::error('{$model} not found', 404);
        }

        return Response::success(null, '{$model} deleted successfully');
    }
}

PHP;
    }

    private function routeTemplate(string $resource, string $controllerClass): string
    {
        return str_replace(
            ['{{resource}}', '{{controller}}'],
            [$resource, $controllerClass],
            <<<'PHP'
// Generated by: php siro make:api {{resource}}
$app->router->get('/{{resource}}', [\App\Controllers\{{controller}}::class, 'index']);
$app->router->get('/{{resource}}/{id}', [\App\Controllers\{{controller}}::class, 'show']);
$app->router->post('/{{resource}}', [\App\Controllers\{{controller}}::class, 'store']);
$app->router->put('/{{resource}}/{id}', [\App\Controllers\{{controller}}::class, 'update']);
$app->router->delete('/{{resource}}/{id}', [\App\Controllers\{{controller}}::class, 'delete']);
PHP
        );
    }
}
