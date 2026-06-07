<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TagRepository;

final class TagService extends AbstractService
{
    public function __construct(TagRepository $repo)
    {
        parent::__construct($repo);
    }
}
