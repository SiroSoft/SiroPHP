---
title: Starter Template
description: Copy, run, and ship a SiroPHP API
sidebar_position: 12
sidebar_label: Starter Template
---

# SiroPHP Starter Template

`SiroPHP` is the canonical application skeleton used by `siro new` and by the
`sirosoft/api` Composer project. Start with it when you need a production-ready
API, not an empty framework install.

## Create a project

### Composer

```bash
composer create-project sirosoft/api my-api --no-interaction
cd my-api
php siro key:generate
```

### Siro CLI

If the Siro PHAR is installed:

```bash
siro new my-api
cd my-api
composer install
php siro key:generate
```

Use an absolute path when the project belongs elsewhere:

```bash
siro new /workspaces/my-api
```

Both paths create the same application shape. A real `.env` is never copied
from the framework source; a new local JWT secret is generated instead.

## Run the first API

The default SQLite configuration is suitable for local development:

```bash
php siro migrate
php siro make:auth
php siro migrate
php siro make:crud products
php siro migrate
php siro serve
```

In another terminal, verify the generated endpoint:

```bash
php siro api:test GET /api/products
```

The generated CRUD includes the model, migration, controller, resource,
routes, and feature test. Review and commit generated files before changing
the API contract.

## Generate the API contract

Generate OpenAPI and a Postman collection after routes stabilize:

```bash
php siro make:openapi --with-swagger
php siro make:postman
```

Run the runtime contract check before handing the API to a frontend:

```bash
php siro api:contract --spec=public/openapi.json
```

The Next.js and Nuxt admin starters are separate frontend consumers of this
contract. They are already deployed and should be connected through the API
base URL rather than copied into this backend skeleton.

## Local quality gate

```bash
php siro test
composer analyse
php siro env:check
```

For a focused CRUD smoke test:

```bash
php siro api:test GET /api/products
php siro api:test POST /api/products name=Laptop price=999.99
php siro api:test GET /api/products
```

## Ship with Docker

The starter includes Docker files for local and production-style runs:

```bash
docker compose up -d
```

For a production deployment, use the deployment configuration and keep the
Composer lock file in the release artifact:

```bash
php siro config:cache
php siro env:check
php siro deploy --init
php siro deploy
```

Never commit `.env`, generated secrets, `storage/logs`, or local SQLite files.
Commit `composer.lock` for application deployments so the production runtime
uses the tested dependency graph.

## Starter layout

```text
my-api/
|- app/                 Application code
|- config/              Runtime configuration
|- database/             Migrations and seeds
|- public/               HTTP entry point and generated API docs
|- routes/               API and web routes
|- storage/              Local runtime data and traces
|- tests/                Unit, integration, and feature tests
|- .env.example          Safe configuration template
|- Dockerfile            Production container build
|- docker-compose.yml    Local service orchestration
`- siro                  Project CLI entry point
```

## Next task

Choose the guide that matches the work:

- [Authentication](AUTHENTICATION.md)
- [Database](DATABASE.md)
- [Testing](TESTING.md)
- [Deployment](DEPLOYMENT.md)
- [Debug workflow](DEBUG_WORKFLOW.md)
