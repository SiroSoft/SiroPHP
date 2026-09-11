# SiroPHP Skeleton — Backend UAT Checklist (v1.0.x)

Manual acceptance run against a deployed skeleton (e.g. `https://skeleton.sirophp.com`).
Automated coverage: `composer test` (Unit/Integration/Feature/EdgeCase/Cli) + `php scripts/check-mapping.php`.

FE = `https://admin-next.sirophp.com` or `https://admin-nuxt.sirophp.com`.
All `/api` calls need header `X-Siro-FE: <FE_SHARED_SECRET>` (+ `Origin` of one FE in browsers).

## 1. Auth + Turnstile (REQ-2/REQ-9)
- [ ] `POST /api/auth/login` **without** `cf-turnstile-response` → 422 `cf-turnstile-response`
- [ ] `POST /api/auth/register` **without** token → 422 `cf-turnstile-response`
- [ ] `POST /api/auth/forgot-password` **without** token → 422 `cf-turnstile-response`
- [ ] Login with valid Turnstile token → 200 + `token` + `refresh_token`
- [ ] 1-click demo login on both FEs fills `demo@skeleton.sirophp.com` and lands on dashboard

## 2. FE-only API access (REQ-3)
- [ ] Browser `Origin: https://evil.example.com` on `/api/*` → 403 (nginx layer)
- [ ] Missing/invalid `X-Siro-FE` → 403 `fe` (FeGuard layer)
- [ ] Valid `X-Siro-FE` + FE origin → 200

## 3. Demo read-only (REQ-5)
- [ ] Demo user GET list/detail → 200
- [ ] Demo user POST/PUT/DELETE → 403 `demo` (DemoGuard)

## 4. CRUD mapping (REQ-4)
- [ ] `php scripts/check-mapping.php` → `49 routes OK`, exit 0
- [ ] products/categories/tags/orders/posts/users: list, create, detail, update, delete, validation 422s
- [ ] `PATCH /api/orders/{id}/status`, `GET /api/dashboard/stats` (`orders_by_status`), `GET /api/health/*`

## 5. Uploads
- [ ] `POST /api/upload` multipart → 201, `url` starts with `/storage/`
- [ ] `GET <url>` → 200 image bytes (needs `public/storage` symlink: `php siro storage:link`)
- [ ] > `UPLOAD_MAX_MB` → 422; disallowed extension → 422

## 6. Seeds (English)
- [ ] Fresh `php siro db:seed`: admin + demo users, 8 categories, 12 tags, 12 products, 8 orders, 6 posts — all English, no lorem ipsum

## 7. Prod hardening
- [ ] `php siro doctor --prod` → all pass; `composer audit` → clean
- [ ] Security headers present (`nosniff`, `DENY`); `APP_DEBUG=false`; HTTPS
