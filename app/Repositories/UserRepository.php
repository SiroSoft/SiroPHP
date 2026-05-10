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

    public function findByEmail(string $email): mixed
    {
        $result = $this->model->where('email', '=', strtolower(trim($email)))->limit(1)->get();
        return $result[0] ?? null;
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->model->query()->orderBy('id', 'DESC')->paginate($perPage, $page);
    }
}
