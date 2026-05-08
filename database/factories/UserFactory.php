<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;

/**
 * Factory for generating User model instances.
 *
 * Usage:
 *   $user = UserFactory::new()->create();
 *   $users = UserFactory::new()->count(10)->create();
 */
final class UserFactory
{
    private int $count = 1;
    /** @var array<string, mixed> */
    private array $overrides = [];

    public static function new(): self
    {
        return new self();
    }

    public function count(int $count): self
    {
        $this->count = max(1, $count);
        return $this;
    }

    /** @param array<string, mixed> $data */
    public function with(array $data): self
    {
        $this->overrides = $data;
        return $this;
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $suffix = bin2hex(random_bytes(4));
        return [
            'name' => 'User_' . $suffix,
            'email' => 'user_' . $suffix . '@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'status' => 1,
            'token_version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /** @return User|array<int, User> */
    public function create(): User|array
    {
        if ($this->count === 1) {
            return User::create(array_merge($this->definition(), $this->overrides));
        }

        $results = [];
        for ($i = 0; $i < $this->count; $i++) {
            $results[] = User::create(array_merge($this->definition(), $this->overrides));
        }
        return $results;
    }
}
