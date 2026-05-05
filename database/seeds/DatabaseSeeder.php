<?php

declare(strict_types=1);

/**
 * Database seeder orchestration.
 *
 * Add seeder classes to the `$calls` array in the order they should run.
 * Run with: php siro db:seed
 */
final class DatabaseSeeder
{
    /** @var array<int, string> Seeder class names in run order */
    public array $calls = [
        // UserSeeder::class,
    ];

    public function run(): void
    {
        foreach ($this->calls as $class) {
            $seeder = new $class();
            if (method_exists($seeder, 'run')) {
                $seeder->run();
            }
        }
    }
}
