<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use Siro\Core\Model;

final class CategoryRepository extends BaseRepository
{
    protected function createModel(): Model
    {
        return new Category();
    }
}
