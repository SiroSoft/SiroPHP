# Siro API Response Contract v1

## Standard Envelope

Every API response follows this envelope:

```json
{
  "success": true | false,
  "message": "Human-readable message",
  "data": { ... } | [ ... ] | null,
  "meta": { ... }
}
```

- `success`: Always present. `true` for 2xx, `false` for 4xx/5xx.
- `message`: Human-readable summary. Never null.
- `data`: Response payload. `null` for empty results, deletions, or errors.
- `meta`: Metadata (pagination, timestamps). `{}` when empty.

---

## 1. Success Response

**HTTP 200**

```json
{
  "success": true,
  "message": "Products list",
  "data": { ... },
  "meta": {
    "timestamp": "2026-05-29T12:00:00+00:00"
  }
}
```

**Schema:** `#/components/schemas/SuccessResponse`

```yaml
SuccessResponse:
  type: object
  properties:
    success:
      type: boolean
      example: true
    message:
      type: string
      example: Operation successful
    data: {}
    meta:
      type: object
      properties:
        timestamp:
          type: string
          format: date-time
```

---

## 2. Error Response

**HTTP 400 / 401 / 403 / 404 / 500**

```json
{
  "success": false,
  "message": "Product not found"
}
```

**Schema:** `#/components/schemas/ErrorResponse`

```yaml
ErrorResponse:
  type: object
  properties:
    success:
      type: boolean
      example: false
    message:
      type: string
      example: Not found
```

---

## 3. Validation Error Response

**HTTP 422**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Schema:** `#/components/schemas/ValidationErrorResponse`

```yaml
ValidationErrorResponse:
  type: object
  properties:
    success:
      type: boolean
      example: false
    message:
      type: string
      example: Validation failed
    errors:
      type: object
      additionalProperties:
        type: array
        items:
          type: string
```

---

## 4. Pagination Response

**HTTP 200 — List endpoints**

```json
{
  "success": true,
  "message": "Products list",
  "data": [ ... ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5,
    "timestamp": "2026-05-29T12:00:00+00:00"
  }
}
```

**Schema:** `#/components/schemas/PaginatedResponse`
**Meta Schema:** `#/components/schemas/PaginationMeta`

```yaml
PaginationMeta:
  type: object
  properties:
    page:
      type: integer
      example: 1
    per_page:
      type: integer
      example: 20
    total:
      type: integer
      example: 100
    last_page:
      type: integer
      example: 5
    timestamp:
      type: string
      format: date-time
```

---

## 5. Auth Response

**HTTP 200 (login) / HTTP 201 (register)**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ...",
    "refresh_token": "def...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    }
  },
  "meta": {
    "timestamp": "2026-05-29T12:00:00+00:00"
  }
}
```

**Schema:** `#/components/schemas/AuthTokenResponse`

```yaml
AuthTokenResponse:
  type: object
  properties:
    success:
      type: boolean
      example: true
    message:
      type: string
      example: Login successful
    data:
      type: object
      properties:
        token:
          type: string
          example: eyJ...
        refresh_token:
          type: string
          example: eyJ...
        token_type:
          type: string
          example: Bearer
        expires_in:
          type: integer
          example: 3600
        user:
          $ref: '#/components/schemas/User'
```

---

## 6. Upload Response

**HTTP 201**

```json
{
  "success": true,
  "message": "Avatar uploaded",
  "data": {
    "path": "avatars/abc123.jpg",
    "url": "avatars/abc123.jpg",
    "original_name": "profile.jpg",
    "size": 204800,
    "mime": "image/jpeg"
  },
  "meta": {
    "timestamp": "2026-05-29T12:00:00+00:00"
  }
}
```

**Schema:** `#/components/schemas/UploadResponse`

```yaml
UploadResponse:
  type: object
  properties:
    success:
      type: boolean
      example: true
    message:
      type: string
      example: File uploaded
    data:
      type: object
      properties:
        path:
          type: string
          example: avatars/abc123.jpg
        url:
          type: string
          example: avatars/abc123.jpg
        original_name:
          type: string
          example: profile.jpg
        size:
          type: integer
          example: 204800
        mime:
          type: string
          example: image/jpeg
```

---

## 7. Created Response

**HTTP 201**

```json
{
  "success": true,
  "message": "Product created",
  "data": { ... },
  "meta": {
    "timestamp": "2026-05-29T12:00:00+00:00"
  }
}
```

Same schema as `SuccessResponse`. HTTP status 201 instead of 200.

---

## 8. No Content Response

**HTTP 204 — Delete endpoints**

No body. Empty response with `204 No Content` status.

---

## Quick Reference

| Scenario | HTTP Status | `success` | `data` | `meta` | `errors` |
|---|---|---|---|---|---|
| List | 200 | true | `[...]` | `{page, per_page, total, last_page, timestamp}` | — |
| Detail | 200 | true | `{...}` | `{timestamp}` | — |
| Create | 201 | true | `{...}` | `{timestamp}` | — |
| Update | 200 | true | `{...}` | `{timestamp}` | — |
| Delete | 204 | — | — | — | — |
| Login/Register | 200/201 | true | `{token, ...}` | `{timestamp}` | — |
| Upload | 201 | true | `{path, url, ...}` | `{timestamp}` | — |
| Validation error | 422 | false | null | — | `{field: [msg]}` |
| Not found | 404 | false | null | — | — |
| Auth error | 401 | false | null | — | — |
| Forbidden | 403 | false | null | — | — |
| Server error | 500 | false | null | — | — |
