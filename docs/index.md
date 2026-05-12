# SiroPHP Documentation

**Complete guide to building production-ready APIs with SiroPHP**

---

## 🚀 Getting Started

### New to SiroPHP?
1. **[Quick Start Guide](guides/QUICKSTART.md)** - Build your first API in 5 minutes
2. **[README](../README.md)** - Overview and features
3. **[Installation](../README.md#installation)** - Setup instructions

### Ready to Deploy?
- **[Deployment Guide](guides/DEPLOYMENT.md)** - Production deployment
- **[Security Guide](SECURITY.md)** - Security hardening
- **[Performance Guide](PERFORMANCE.md)** - Optimization tips

---

## 📚 Guides

### Essential Guides
- **[Quick Start](guides/QUICKSTART.md)** ⭐ - 5-minute tutorial
- **[Deployment](guides/DEPLOYMENT.md)** ⭐ - Production deployment
- **[Architecture](ARCHITECTURE.md)** - Design decisions (ADRs)
- **[Security](SECURITY.md)** - Security best practices
- **[Performance](PERFORMANCE.md)** - Optimization techniques

### Development Guides
- **[Database Guide](guides/DATABASE.md)** - Multi-DB support, migrations
- **[Authentication Guide](guides/AUTHENTICATION.md)** - JWT, RBAC
- **[Testing Guide](guides/TESTING.md)** - PHPUnit tests
- **[Validation Guide](guides/VALIDATION.md)** - Request validation
- **[File Upload Guide](guides/FILE_UPLOAD.md)** - File handling

### Advanced Guides
- **[Queue & Mail](guides/QUEUE_MAIL.md)** - Background jobs
- **[Event System](guides/EVENTS.md)** - Pub/sub pattern
- **[Caching Guide](guides/CACHING.md)** - Cache strategies
- **[API Versioning](guides/API_VERSIONING.md)** - Version management
- **[Multi-language](guides/I18N.md)** - Internationalization

---

## 🔍 API References

### Core Components
- **[Router](api/Router.md)** - HTTP routing
- **[Model](api/Model.md)** - ORM and relationships
- **[Controller](api/Controller.md)** - Request handling
- **[Response](api/Response.md)** - Response building
- **[Request](api/Request.md)** - Input handling
- **[Database](api/Database.md)** - Query builder
- **[Auth](api/Auth.md)** - Authentication
- **[Middleware](api/Middleware.md)** - Request processing

### Utilities
- **[Validator](api/Validator.md)** - Input validation
- **[Cache](api/Cache.md)** - Caching system
- **[Session](api/Session.md)** - Session management
- **[Logger](api/Logger.md)** - Logging and tracing
- **[HTTP Client](api/Http.md)** - Outbound requests
- **[Storage](api/Storage.md)** - File storage
- **[Queue](api/Queue.md)** - Job queue
- **[Mail](api/Mail.md)** - Email sending
- **[Events](api/Events.md)** - Event dispatcher
- **[Encrypter](api/Encrypter.md)** - Encryption

---

## 🛠️ CLI Commands Reference

### Project Setup
```bash
php siro new my-api              # Create new project
php siro serve                   # Start dev server
php siro live                    # Dev server with auto-reload
```

### Code Generation
```bash
php siro make:model User         # Generate model
php siro make:controller User    # Generate controller
php siro make:migration create_users_table  # Generate migration
php siro make:crud products      # Full CRUD scaffold
php siro make:test ProductApi    # Generate test
php siro make:factory User       # Generate factory
php siro make:auth               # Full auth system
php siro make:resource User      # Generate resource
php siro make:job SendEmail      # Generate job
php siro make:mail Welcome       # Generate mail class
php siro make:event UserCreated  # Generate event
php siro make:lang vi            # Create language pack
php siro make:openapi            # Generate OpenAPI spec
php siro make:postman            # Generate Postman collection
```

### Database
```bash
php siro migrate                 # Run migrations
php siro migrate:rollback        # Rollback migrations
php siro migrate:status          # Check migration status
php siro db:seed                 # Run seeders
php siro db:show users           # Inspect table
```

### Testing & Debugging
```bash
php siro test                    # Run all tests
php siro api:test GET /api/users # Test endpoint
php siro log:trace <id>          # View trace details
php siro log:replay <id>         # Replay request
php siro log:export --format=json # Export traces
php siro slow                    # Show slow requests
php siro rate:status             # Rate limit dashboard
```

### Performance
```bash
php siro benchmark               # Run benchmarks
php siro config:cache            # Cache configuration
php siro optimize                # Optimize for production
php siro env:check               # Validate environment
```

### Deployment
```bash
php siro deploy                  # Deploy application
php siro down                    # Enable maintenance mode
php siro up                      # Disable maintenance mode
php siro storage:link            # Create storage symlink
```

### Queue & Schedule
```bash
php siro queue:work              # Process jobs
php siro queue:work --daemon     # Run continuously
php siro queue:status            # Queue status
php siro queue:retry <id>        # Retry failed job
php siro schedule:run            # Run scheduled tasks
```

### System
```bash
php siro route:list              # List all routes
php siro route:rules             # Extract validation rules
php siro key:generate            # Generate JWT secret
php siro doctor                  # System health check
php siro env:switch staging      # Switch environment
```

**Full command list:** `php siro list`

---

## 🎯 Common Tasks

### I want to...

#### Build a REST API
→ See **[Quick Start Guide](guides/QUICKSTART.md)**  
→ Use `php siro make:crud posts`

#### Add Authentication
→ See **[Authentication Guide](guides/AUTHENTICATION.md)**  
→ Use `php siro make:auth`

#### Deploy to Production
→ See **[Deployment Guide](guides/DEPLOYMENT.md)**  
→ Use `php siro deploy`

#### Write Tests
→ See **[Testing Guide](guides/TESTING.md)**  
→ Use `php siro make:test ProductApi`

#### Optimize Performance
→ See **[Performance Guide](PERFORMANCE.md)**  
→ Run `php siro benchmark`

#### Secure My API
→ See **[Security Guide](SECURITY.md)**  
→ Run `php siro env:check`

#### Add File Upload
→ See **[File Upload Guide](guides/FILE_UPLOAD.md)**  
→ Use `$request->file('avatar')`

#### Queue Heavy Operations
→ See **[Queue Guide](guides/QUEUE_MAIL.md)**  
→ Use `Mail::to($user)->queue()`

---

## 📖 Learning Path

### Beginner (Week 1)
1. Install SiroPHP
2. Follow Quick Start tutorial
3. Build simple CRUD API
4. Add authentication
5. Write basic tests

### Intermediate (Week 2-3)
1. Learn middleware system
2. Implement relationships
3. Add caching
4. Set up queue system
5. Configure multi-language support

### Advanced (Week 4+)
1. Study architecture decisions
2. Optimize performance
3. Implement custom middleware
4. Extend framework components
5. Deploy to production

---

## 💡 Examples

### Example Applications
- **Blog API** - [View on GitHub](https://github.com/SiroSoft/examples/tree/main/blog-api)
- **E-commerce Backend** - [View on GitHub](https://github.com/SiroSoft/examples/tree/main/ecommerce)
- **Task Manager** - [View on GitHub](https://github.com/SiroSoft/examples/tree/main/task-manager)
- **Real-time Chat** - Coming soon

### Code Snippets
- **Authentication** - [Examples](examples/auth.md)
- **CRUD Operations** - [Examples](examples/crud.md)
- **File Upload** - [Examples](examples/file-upload.md)
- **Queue Jobs** - [Examples](examples/queue.md)
- **API Versioning** - [Examples](examples/versioning.md)

---

## 🔗 External Resources

### Official Links
- **GitHub:** https://github.com/SiroSoft/SiroPHP
- **Core Library:** https://github.com/SiroSoft/siro-core
- **Packagist:** https://packagist.org/packages/sirosoft/api
- **Issues:** https://github.com/SiroSoft/SiroPHP/issues
- **Discussions:** https://github.com/SiroSoft/SiroPHP/discussions

### Community
- **Discord:** [Join our server](https://discord.gg/sirophp) *(placeholder)*
- **Twitter:** [@SiroPHP](https://twitter.com/sirophp) *(placeholder)*
- **Blog:** https://sirosoft.com/blog *(placeholder)*

### Documentation
- **Core Framework Docs:** https://github.com/SiroSoft/siro-core/tree/main/docs
- **OpenAPI Spec:** [View](openapi.json)
- **Swagger UI:** http://localhost:8000/docs/swagger/ *(after running `make:openapi`)*

---

## ❓ FAQ

### General Questions

**Q: Is SiroPHP production-ready?**  
A: Yes! Used in production by multiple companies. See [Deployment Guide](guides/DEPLOYMENT.md) for hardening tips.

**Q: How does it compare to Laravel?**  
A: 2000-4000x faster, 40x less memory, zero dependencies. Trade-off: smaller ecosystem.

**Q: Can I use Eloquent ORM?**  
A: No, SiroPHP has its own lightweight Model layer. Similar API, less overhead.

**Q: Does it support PostgreSQL?**  
A: Yes! MySQL, PostgreSQL, and SQLite are fully supported.

### Technical Questions

**Q: How do I add custom middleware?**  
A: Create class implementing middleware interface, add to route. See [Router API](api/Router.md).

**Q: How do I handle file uploads?**  
A: Use `$request->file()` method. See [File Upload Guide](guides/FILE_UPLOAD.md).

**Q: Can I use Redis?**  
A: Yes! Configure in `.env`: `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`.

**Q: Is there WebSocket support?**  
A: Not yet. Planned for future release. Use external WebSocket server for now.

### Deployment Questions

**Q: What hosting works with SiroPHP?**  
A: Any PHP 8.2+ host. Works on $2/month shared hosting, VPS, Docker, etc.

**Q: How do I scale horizontally?**  
A: Use load balancer + multiple app servers + shared database + Redis cache.

**Q: How do I monitor production?**  
A: Use trace IDs, slow request logs, health check endpoint, external monitoring tools.

---

## 📊 Document Status

### Completed ✅
- Quick Start Guide
- Deployment Guide
- Architecture Decisions (from core)
- Security Guide (from core)
- Performance Guide (from core)
- Router API Reference (from core)

### In Progress 🚧
- Database Guide
- Authentication Guide
- Testing Guide
- More API References

### Planned 📅
- Example applications
- Video tutorials
- Migration guides from Laravel
- Cookbook recipes

---

## 🆘 Getting Help

### Priority Order
1. **Search documentation** - Your question might be answered here
2. **Check GitHub Issues** - Someone may have asked before
3. **Ask in Discussions** - Community can help
4. **Open Issue** - For bugs or feature requests
5. **Email Support** - support@sirosoft.com (for urgent matters)

### Response Times
- GitHub Issues: Within 48 hours
- Discussions: Within 3 days
- Email: Within 1 week
- Security issues: Within 48 hours (security@sirosoft.com)

---

## 🎉 Contributing

Want to improve documentation? We welcome contributions!

1. Fork repository
2. Edit documentation files
3. Submit pull request
4. See [Contributing Guide](../CONTRIBUTING.md)

**Documentation priorities:**
- High: API references, examples
- Medium: Tutorials, guides
- Low: Translations, diagrams

---

*Last updated: May 12, 2026*  
*Documentation version: 0.23*  
*SiroPHP version: 0.23.0*
