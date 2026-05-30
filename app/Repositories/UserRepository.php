<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Siro\Core\DB;
use Siro\Core\Database;
use Siro\Core\Model;

final class UserRepository extends BaseRepository
{
    protected function createModel(): Model
    {
        return new User();
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $rows = User::where('email', '=', $email)->limit(1)->get();
        if ($rows === []) return null;
        $data = $rows[0]->toArray();
        $data['password'] = $rows[0]->getAttribute('password');
        return $data;
    }

    /** @return array<string, mixed>|null */
    public function findBy(string $column, mixed $value): ?array
    {
        $rows = User::where($column, '=', $value)->limit(1)->get();
        if ($rows === []) return null;
        $data = $rows[0]->toArray();
        $data['password'] = $rows[0]->getAttribute('password');
        return $data;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = $this->model->query()->orderBy('id', 'DESC');
        if (isset($filters['status'])) {
            $query->where('status', '=', $filters['status']);
        }
        if (isset($filters['role'])) {
            $query->where('role', '=', $filters['role']);
        }
        return $query->paginate($perPage, $page);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Model
    {
        return User::create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateWhere(string $column, mixed $value, array $data): int
    {
        return User::where($column, '=', $value)->limit(1)->update($data);
    }

    public function atomicIncrement(string $idField, int $idValue, string $field, int $amount = 1): void
    {
        $table = (new User())->getTable();
        $allowedFields = ['login_attempts'];
        $allowedIdFields = ['id', 'user_id'];
        if (!in_array($field, $allowedFields, true)) {
            throw new \InvalidArgumentException("Invalid field: $field");
        }
        if (!in_array($idField, $allowedIdFields, true)) {
            throw new \InvalidArgumentException("Invalid idField: $idField");
        }
        Database::execute(
            "UPDATE {$table} SET `{$field}` = `{$field}` + ? WHERE `{$idField}` = ?",
            [$amount, $idValue]
        );
    }

    public function incrementWhere(string $column, mixed $value, string $field, int $amount): void
    {
        $user = User::where($column, '=', $value)->limit(1)->first();
        if ($user === null) return;
        $current = $user->getAttribute($field);
        $currentValue = is_numeric($current) ? (int) $current : 0;
        User::where($column, '=', $value)->limit(1)->update([
            $field => $currentValue + $amount,
        ]);
    }
}
