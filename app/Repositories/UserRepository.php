<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
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
     * @param array<string, string> $filters
     * @return array{data: array<int, mixed>, meta: array<string, mixed>}
     */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->model->query()->orderBy('id', 'DESC')->paginate($perPage, $page);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Model
    {
        return User::create($data);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $user = User::find($id);
        if ($user === null) return null;
        $data = $user->toArray();
        $data['password'] = $user->getAttribute('password');
        return $data;
    }

    /** @param array<string, mixed> $data */
    public function updateWhere(string $column, mixed $value, array $data): void
    {
        User::where($column, '=', $value)->limit(1)->update($data);
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
