<?php

declare(strict_types=1);

namespace Siro\Core;

use Siro\Core\Commands\MakeApiCommand;
use Siro\Core\Commands\MakeControllerCommand;
use Siro\Core\Commands\MakeMigrationCommand;
use Siro\Core\Commands\MakeResourceCommand;
use Siro\Core\Commands\MigrateCommand;

final class Console
{
    public function __construct(private readonly string $basePath)
    {
    }

    /** @param array<int, string> $argv */
    public function run(array $argv): int
    {
        $command = trim($argv[1] ?? '');
        $args = array_slice($argv, 2);

        if ($command === '' || in_array($command, ['-h', '--help', 'help'], true)) {
            $this->printHelp();
            return 0;
        }

        switch ($command) {
            case 'make:api':
                return (new MakeApiCommand($this->basePath))->run($args);
            case 'make:controller':
                return (new MakeControllerCommand($this->basePath))->run($args);
            case 'make:migration':
                return (new MakeMigrationCommand($this->basePath))->run($args);
            case 'make:resource':
                return (new MakeResourceCommand($this->basePath))->run($args);
            case 'migrate':
                return (new MigrateCommand($this->basePath))->run($args);
            default:
                return $this->unknownCommand($command);
        }
    }

    private function printHelp(): void
    {
        $this->write('Siro Console');
        $this->write('Usage:');
        $this->write('  php siro make:api users');
        $this->write('  php siro make:controller UserController');
        $this->write('  php siro make:migration create_users_table');
        $this->write('  php siro make:resource UserResource');
        $this->write('  php siro migrate');
    }

    private function unknownCommand(string $command): int
    {
        $this->write('Unknown command: ' . $command);
        $this->printHelp();
        return 1;
    }

    private function write(string $line): void
    {
        echo $line . PHP_EOL;
    }
}
