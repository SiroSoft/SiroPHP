# How to Add a New Module (6 Steps)

This guide walks through adding a new resource (e.g. `Brands`) to the API.

## Step 1: Database Migration

Create a migration file in `database/migrations/`:

```php
<?php
// database/migrations/2026_05_30_000001_create_brands_table.php

use Siro\Core\Database;
use Siro\Core\Schema;

return new class
{
    public function up(): void
    {
        Schema::create('brands', function ($table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
```

Run the migration:
```bash
php siro migrate
```

## Step 2: Model

Create `app/Models/Brand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property string $created_at
 * @property string|null $updated_at
 */
final class Brand extends Model
{
    protected string $table = 'brands';

    protected array $casts = [
        'id' => 'int',
        'is_active' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $fillable = [
        'name',
        'description',
        'is_active',
    ];
}
```

## Step 3: Service + Repository

Create `app/Repositories/BrandRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Brand;
use Siro\Core\Model;

final class BrandRepository extends BaseRepository
{
    protected function createModel(): Model
    {
        return new Brand();
    }
}
```

Create `app/Services/BrandService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BrandRepository;

final class BrandService implements BaseService
{
    public function __construct(private readonly BrandRepository $repo)
    {
    }

    /** Get paginated list of brands. */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($filters, $page, $perPage);
    }

    /** Find a brand by ID. Returns null if not found. */
    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /** Create a new brand. */
    public function create(array $data): mixed
    {
        return $this->repo->store($data);
    }

    /** Update a brand. Returns null if not found. */
    public function update(int $id, array $data): mixed
    {
        return $this->repo->update($id, $data);
    }

    /** Delete a brand. Returns true if deleted. */
    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
```

## Step 4: Controller

Create `app/Controllers/BrandController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\BrandResource;
use App\Role;
use App\Services\BrandService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class BrandController extends Controller
{
    public function __construct(private readonly BrandService $service)
    {
    }

    /**
     * List all brands with pagination.
     *
     * GET /api/brands?page=1&per_page=20
     */
    public function index(Request $request): Response
    {
        $result = $this->service->getAll(
            page: max(1, $request->queryInt('page', 1)),
            perPage: min(100, max(1, $request->queryInt('per_page', 20)))
        );
        return $this->paginated(
            BrandResource::collection($result['data']),
            $result['meta'],
            'Brands list'
        );
    }

    /**
     * Get a single brand by ID.
     *
     * GET /api/brands/{id}
     */
    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return $this->error('Invalid id', 422);
        $item = $this->service->getById($id);
        if ($item === null) return $this->error('Brand not found', 404);
        return $this->success(BrandResource::make($item), 'Brand detail');
    }

    /**
     * Create a new brand.
     *
     * POST /api/brands
     * Body: { name: string, description?: string, is_active?: bool }
     */
    public function store(Request $request): Response
    {
        $validated = $this->validate(['name' => 'required|min:1|max:100']);
        $item = $this->service->create($validated);
        return $this->created(BrandResource::make($item), 'Brand created');
    }

    /**
     * Update a brand.
     *
     * PUT /api/brands/{id}
     * Body: { name?: string, description?: string, is_active?: bool }
     */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return $this->error('Invalid id', 422);
        $validated = $this->validate(['name' => 'min:1|max:100']);
        $item = $this->service->update($id, $validated);
        if ($item === null) return $this->error('Brand not found', 404);
        return $this->success(BrandResource::make($item), 'Brand updated');
    }

    /**
     * Delete a brand.
     *
     * DELETE /api/brands/{id}
     */
    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return $this->error('Invalid id', 422);
        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Brand not found', 404);
    }
}
```

## Step 5: Resource

Create `app/Resources/BrandResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class BrandResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'name' => $this->data['name'] ?? null,
            'description' => $this->data['description'] ?? null,
            'is_active' => (bool) ($this->data['is_active'] ?? true),
            'created_at' => $this->data['created_at'] ?? null,
            'updated_at' => $this->data['updated_at'] ?? null,
        ];
    }
}
```

## Step 6: Route

Add to `routes/api.php` inside the `/api` group:

```php
$router->resource('brands', \App\Controllers\BrandController::class, ['auth', 'throttle:60,1']);
```

## Summary

| Step | File | Purpose |
|------|------|---------|
| 1 | `database/migrations/..._create_brands_table.php` | Database schema |
| 2 | `app/Models/Brand.php` | Eloquent-style model |
| 3 | `app/Repositories/BrandRepository.php` + `app/Services/BrandService.php` | Data access + business logic |
| 4 | `app/Controllers/BrandController.php` | HTTP request handling |
| 5 | `app/Resources/BrandResource.php` | JSON response formatting |
| 6 | `routes/api.php` | URL routing |
