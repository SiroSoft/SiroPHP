<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;

final class CategoryService extends BaseService
{
    public function __construct(CategoryRepository $repo)
    {
        parent::__construct($repo);
    }
}
