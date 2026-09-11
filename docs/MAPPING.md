# API ↔ Frontend ↔ DB Mapping (REQ-4)

> Single source of truth: `routes/api.php` → `public/openapi.json` (via `php scripts/check-mapping.php`)
> → FE `types/api.ts` (regenerated) → services → forms. DB columns via migrations.

## Gate

```bash
php scripts/check-mapping.php   # 49 routes must be covered, exit 0 or CI fails
php siro make:openapi --version=$(php -r 'require "vendor/autoload.php"; echo \Siro\Core\Console::getVersion();') --force
```

## Matrix (skeleton v1.0.1 + core 1.0.0)

| BE route | FE service (next/nuxt) | Request shape | Response envelope | DB table |
|---|---|---|---|---|
| POST /api/auth/register | auth.service register | `{name,email,password,password_confirmation}` | `{success,data:{token,refresh_token,user}}` | users |
| POST /api/auth/login | auth.service login | `{email,password,cf-turnstile-response?}` | same as register | users |
| POST /api/auth/refresh | api.ts interceptor auto | `{refresh_token}` | token pair | refresh_tokens |
| POST /api/auth/logout | auth.service logout | `{}` + Bearer | `{success}` + `token_version++` | users |
| GET /api/auth/me | use-auth/me | Bearer | `{success,data:user}` | users |
| CRUD users/products/categories/tags/orders/posts | *.service list/get/create/update/delete | `PaginationParams{page,per_page,search,sort,order,category_id,min_price,max_price}` | `{success,data[],meta}` | matching tables |
| PATCH /api/orders/{id}/status | orders.service updateStatus | `{status}` | `{success,data}` | orders |
| GET/PUT /api/profile, PUT /profile/password | profile.service | flat object | `{success,data}` | users |
| GET/PUT /api/settings (admin) | settings.service | `{key:value}` object | `{success,data}` | settings(`key`,`value`) |
| POST /api/upload (`file`), /api/upload/avatar (`avatar`) | upload.service multipart | `FormData` | `{success,data:{url}}` | storage disk |
| GET /api/server/info | server.service (footer) | — | `{server,php,db,time}` | — |
| GET /api/dashboard/stats | dashboard.service | — | `{total_*,recent_activity,api_status,orders_by_status,monthly_revenue}` | users/orders/products |
| GET /health, /health/live, /health/ready | health badge (`/health*`, NOT `/api/health`) | — | `{status,version:1.0.1,database}` | — |
| 422 errors | interceptor unwrap | — | `{success:false,message,meta:{errors}}` ← FE reads `meta.errors` first, fallback top-level `errors` | — |

## Known legacy aliases (kept for compat)

- `is_active` query param → maps to `status` server-side; prefer `status/category_id` (v1.0).
- Demo account (`role=viewer`, `demo@…`): `DemoGuardMiddleware` returns `403 demo.readonly` on POST/PUT/PATCH/DELETE.
- FE-only header `X-Siro-FE` (`FeGuardMiddleware`, demo branch): `403` without token; `/health*` exempt.
