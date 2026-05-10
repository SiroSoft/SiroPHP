<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tag;
use Siro\Core\Model;

final class TagRepository extends BaseRepository
{
    protected function createModel(): Model
    {
        return new Tag();
    }
}
