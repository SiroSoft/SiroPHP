<?php

declare(strict_types=1);

namespace Siro\Core\Commands;

use PDO;
use Siro\Core\Database;
use Siro\Core\Env;
use Throwable;

final class MigrateRollbackCommand
{
    use CommandSupport;

    public function __construct(private readonly string $basePath)
    {
    }

    /** @param array<int, string> $args */
    public function run(array $args): int
    {
        $step = $this->parseStep($args);
        if ($step <= 0) {
            $this->write('Invalid --step value. Example: php siro migrate:rollback --step=1');
            return 1;
        }

        Env::load($this->basePath . DIRECTORY_SEPARATOR . '.env');
        /** @var array<string, mixed> $config */
        $config = require $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        Database::configure($config);

        $pdo = Database::connection();
        $this->ensureMigrationTable($pdo);
        $migrations = $this->lastAppliedMigrations($pdo, $step);

        if (!is_array($migrations) || $migrations === []) {
            $this->write('Nothing to rollback.');
            return 0;
        }

        $rolledBack = 0;
        foreach ($migrations as $migration) {
            if (!is_string($migration) || $migration === '') {
                continue;
            }

            $path = $this->basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . $migration;
            if (!is_file($path)) {
                $this->write('Rollback failed: missing migration file: ' . $migration);
                return 1;
            }

            $instance = require $path;
            if (!is_object($instance) || !method_exists($instance, 'down')) {
                $this->write('Rollback failed: invalid migration down(): ' . $migration);
                return 1;
            }

            try {
                $pdo->beginTransaction();
                $instance->down($pdo);
                $deleteStmt = $pdo->prepare('DELETE FROM migrations WHERE migration = :migration');
                $deleteStmt->execute(['migration' => $migration]);
                $pdo->commit();
                $rolledBack++;
                $this->write('Rolled back: ' . $migration);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $this->write('Rollback failed: ' . $migration);
                $this->write($e->getMessage());
                return 1;
            }
        }

        if ($rolledBack === 0) {
            $this->write('Nothing to rollback.');
            return 0;
        }

        $this->write('Rollback completed. Rolled back ' . $rolledBack . ' migration(s).');
        return 0;
    }

    /** @return array<int, string> */
    private function lastAppliedMigrations(PDO $pdo, int $step): array
    {
        try {
            $stmt = $pdo->prepare('SELECT migration FROM migrations ORDER BY id DESC LIMIT :step');
            $stmt->bindValue(':step', max(1, $step), PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return is_array($rows) ? array_values(array_filter($rows, 'is_string')) : [];
        } catch (Throwable) {
            return [];
        }
    }

    /** @param array<int, string> $args */
    private function parseStep(array $args): int
    {
        $count = count($args);
        for ($i = 0; $i < $count; $i++) {
            $arg = $args[$i];

            if ($arg === '--step') {
                $next = $args[$i + 1] ?? '';
                return (int) trim((string) $next);
            }

            if (!str_starts_with($arg, '--step=')) {
                continue;
            }

            $value = trim(substr($arg, 7));
            return (int) $value;
        }

        return 1;
    }

    private function ensureMigrationTable(PDO $pdo): void
    {
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $sql = match ($driver) {
            'pgsql' => 'CREATE TABLE IF NOT EXISTS migrations (id BIGSERIAL PRIMARY KEY, migration VARCHAR(255) NOT NULL UNIQUE, batch INT NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)',
            default => 'CREATE TABLE IF NOT EXISTS migrations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL UNIQUE, batch INT NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)',
        };

        $pdo->exec($sql);

        try {
            $pdo->exec('ALTER TABLE migrations ADD COLUMN batch INT NOT NULL DEFAULT 1');
        } catch (Throwable) {
            // already exists
        }
    }
}
