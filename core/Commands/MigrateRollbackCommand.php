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
        unset($args);

        Env::load($this->basePath . DIRECTORY_SEPARATOR . '.env');
        /** @var array<string, mixed> $config */
        $config = require $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        Database::configure($config);

        $pdo = Database::connection();
        $batch = $this->lastBatch($pdo);
        if ($batch <= 0) {
            $this->write('Nothing to rollback.');
            return 0;
        }

        $stmt = $pdo->prepare('SELECT migration FROM migrations WHERE batch = :batch ORDER BY id DESC');
        $stmt->execute(['batch' => $batch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

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

        $this->write('Rollback completed. Rolled back ' . $rolledBack . ' migration(s) from batch ' . $batch . '.');
        return 0;
    }

    private function lastBatch(PDO $pdo): int
    {
        try {
            $stmt = $pdo->query('SELECT MAX(batch) AS max_batch FROM migrations');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($row['max_batch'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }
}
