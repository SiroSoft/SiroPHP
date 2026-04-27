<?php

declare(strict_types=1);

namespace App\Services;

use Siro\Core\Env;
use Siro\Core\Database;
use Throwable;

final class UserService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUsers(): array
    {
        try {
            return Database::cache(60)
                ->select('SELECT id, name, email, created_at FROM users ORDER BY id DESC LIMIT 100');
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return Database::cache(60)->first(
            'SELECT id, name, email, created_at FROM users WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @param array{name: string, email: string} $payload
     * @return array<string, mixed>
     */
    public function createUser(array $payload): array
    {
        $driver = strtolower((string) Env::get('DB_CONNECTION', 'mysql'));

        if (in_array($driver, ['pgsql', 'postgres', 'postgresql'], true)) {
            $created = Database::first(
                'INSERT INTO users (name, email, created_at) VALUES (:name, :email, NOW()) RETURNING id, name, email',
                [
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                ]
            );

            return $created ?? [
                'id' => null,
                'name' => $payload['name'],
                'email' => $payload['email'],
            ];
        }

        Database::execute(
            'INSERT INTO users (name, email, created_at) VALUES (:name, :email, NOW())',
            [
                'name' => $payload['name'],
                'email' => $payload['email'],
            ]
        );

        $id = Database::connection()->lastInsertId();

        return [
            'id' => $id !== '0' ? $id : null,
            'name' => $payload['name'],
            'email' => $payload['email'],
        ];
    }

    /**
     * @param array{name?: string, email?: string} $payload
     * @return array<string, mixed>|null
     */
    public function updateUser(int $id, array $payload): ?array
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            return null;
        }

        $name = (string) ($payload['name'] ?? $existing['name']);
        $email = (string) ($payload['email'] ?? $existing['email']);

        Database::execute(
            'UPDATE users SET name = :name, email = :email WHERE id = :id',
            [
                'id' => $id,
                'name' => $name,
                'email' => $email,
            ]
        );

        return $this->findById($id);
    }

    public function deleteUser(int $id): bool
    {
        $affected = Database::execute('DELETE FROM users WHERE id = :id', ['id' => $id]);
        return $affected > 0;
    }
}
