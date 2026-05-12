# V1.0 Scope Freeze

Status: Draft for team sign-off

## Goal

Ship `v1.0.0` as a stable API-first release with strict release gates, production-ready defaults, and no known critical security gaps.

## In Scope (Must Have)

- Stable public APIs for routing, request/response, middleware, auth, validation, DB/query, storage, CLI essentials
- Security baseline
  - Auth required on write endpoints by default in skeleton
  - JWT access/refresh flow with rotation and revoke behavior documented
  - CORS production-safe defaults documented
  - Log access protection guidance for Nginx/Apache
- Technical release gates enforced in CI
  - `composer release:check` passes on main and release workflows
  - PHPUnit green
  - PHPStan green
  - `composer audit` green
- Production docs baseline
  - Env profiles for local/staging/production
  - Deployment quickstart
  - Security release checklist

## Out of Scope (V1.1+)

- New major features (multi-tenancy framework-level, websocket stack, GraphQL stack)
- Large architectural rewrites
- Performance tuning that changes public behavior
- Non-critical DX polish that does not affect stability/security

## Breaking Change Policy (For v1.0)

- No intentional breaking changes after scope freeze date
- Any required break must be approved by maintainers and documented in migration notes
- Deprecated behavior must include:
  - clear warning path
  - migration instruction
  - removal target version (>= v1.1)

## Definition of Done (v1.0)

- All technical gates pass in CI on release candidate tag
- No open `critical` or `high` severity security issues
- Migration notes available for users upgrading from `0.22.x`
- Release notes include:
  - highlights
  - security-impacting changes
  - upgrade steps

## Owner Checklist

- Framework maintainer signs off API stability
- Security owner signs off checklist completion
- CI owner confirms release gate workflow status
- Docs owner confirms quickstart and env profiles are current

## Proposed Timeline

- Scope freeze: immediate after this document approval
- RC window: 7-14 days
- v1.0.0 tag: after 2 consecutive green days on `main` with no blocker issues
