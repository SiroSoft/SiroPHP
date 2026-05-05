# Production Checklist

## Before deploying SiroPHP to production, verify each item.

### 🔐 Security

- [ ] **JWT_SECRET** — At least 32 random characters. Run: `php siro key:generate`
- [ ] **APP_DEBUG=false** — Never expose debug traces in production
- [ ] **DB_CONNECTION** — Set to `mysql` or `pgsql` (not `sqlite` for production)
- [ ] **DB password** — Strong password, not the default
- [ ] **Log protection** — Verify: `php siro doctor --prod` checks .htaccess / Nginx config
- [ ] **OpenAPI disabled** — `SIRO_OPENAPI_ENABLED` not set (defaults to false in production)
- [ ] **Remove public docs** — Delete `public/openapi.json` and `public/docs.html` if not needed
- [ ] **Signed URLs key** — Same as JWT_SECRET, ensure it's set

### 🖥️ Server Requirements

- [ ] **PHP 8.2+** — `php -v`
- [ ] **Extensions**: `pdo`, `json`, `mbstring`, `openssl`, `curl`
- [ ] **PDO driver**: `pdo_mysql` or `pdo_pgsql`
- [ ] **Web server**: Nginx or Apache with rewrite rules to `public/index.php`
- [ ] **Storage writable**: `storage/logs/`, `storage/cache/`, `storage/framework/`

### 📁 File Permissions

- [ ] `storage/` — Owner: www-data, writable by web server
- [ ] `storage/logs/` — Protected from web access (`.htaccess` or Nginx `deny all`)
- [ ] `public/` — Only `index.php`, `.php` files should be executable
- [ ] `.env` — Never committed to git, never accessible via web

### 📊 Monitoring

- [ ] **Health endpoint**: `GET /health` — Configure load balancer to use this
- [ ] **Log retention**: `LOG_RETENTION_DAYS=30` in `.env`
- [ ] **Slow query threshold**: `DB_SLOW_QUERY_THRESHOLD=100` (ms)
- [ ] **Rate limiting**: Configure throttle on auth endpoints
- [ ] **Queue worker**: `php siro queue:work --daemon` for background jobs

### 🚀 Deployment

- [ ] Run `php siro doctor --prod` — All checks pass
- [ ] Run `php siro migrate` — All migrations applied
- [ ] Run `php siro config:cache` — Cache configuration
- [ ] Run `php siro optimize` — Full optimization
- [ ] Run `php vendor/bin/phpunit` — All 197 tests pass

### 🔄 Maintenance

```bash
# Enable maintenance
php siro down --message="Scheduled maintenance" --allow=YOUR_IP

# Deploy updates
git pull
php siro migrate
php siro config:cache

# Disable maintenance
php siro up
```

### 🐘 PostgreSQL Notes (if using)

- Connection: `DB_CONNECTION=pgsql`, `DB_PORT=5432`
- Extension: `pdo_pgsql`
- Migrations: Schema Builder auto-generates valid PostgreSQL DDL
- Sequences: `RETURNING id` used for INSERT (not `lastInsertId`)
- Indexes: Created as separate `CREATE INDEX` statements

### 🚨 Emergency Commands

```bash
php siro doctor --prod        # Full health check
php siro down --allow=YOUR_IP # Maintenance mode
php siro up                   # Restore
php siro log:trace --status=500  # Find errors
php siro log:slow --limit=20     # Find slow queries
```
