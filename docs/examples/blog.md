# Blog API Example

A complete blog API built with SiroPHP demonstrating CRUD operations, authentication, pagination, sorting, and filtering.

## Models

### Post

| Field | Type | Rules |
|-------|------|-------|
| id | integer | auto-increment |
| title | string | required, min:3, max:255 |
| body | string | required, min:10 |
| image | string|null | optional, file upload |
| locale | string | required, in:en,vi |
| status | string | in:draft,published |
| user_id | integer | foreign key to users |
| created_at | datetime | auto |
| updated_at | datetime | auto |

### Comment

| Field | Type | Rules |
|-------|------|-------|
| id | integer | auto-increment |
| post_id | integer | required, foreign key |
| user_id | integer | required, foreign key |
| body | string | required, min:1 |
| created_at | datetime | auto |
| updated_at | datetime | auto |

### Tag

| Field | Type | Rules |
|-------|------|-------|
| id | integer | auto-increment |
| name | string | required, min:1, max:100 |
| created_at | datetime | auto |

### Category

| Field | Type | Rules |
|-------|------|-------|
| id | integer | auto-increment |
| name | string | required, min:2, max:100 |
| created_at | datetime | auto |

## API Endpoints

### Authentication

All blog endpoints except public reads require authentication via `Bearer` JWT token.

```
POST   /api/auth/register      Register new user
POST   /api/auth/login         Log in
POST   /api/auth/refresh       Refresh access token
POST   /api/auth/logout        Log out (protected)
GET    /api/auth/me            Get current user (protected)
```

### Posts

```
GET    /api/posts              List posts (public, paginated)
GET    /api/posts/{id}         Get post detail (public)
POST   /api/posts              Create post (protected)
PUT    /api/posts/{id}         Update post (protected, owner/admin)
DELETE /api/posts/{id}         Delete post (protected, owner/admin)
```

### Tags

```
GET    /api/tags               List tags (public, paginated)
GET    /api/tags/{id}          Get tag detail (public)
POST   /api/tags               Create tag (protected)
PUT    /api/tags/{id}          Update tag (protected)
DELETE /api/tags/{id}          Delete tag (protected)
```

### Categories

```
GET    /api/categories         List categories (public, paginated)
GET    /api/categories/{id}    Get category detail (public)
POST   /api/categories         Create category (protected)
PUT    /api/categories/{id}    Update category (protected)
DELETE /api/categories/{id}    Delete category (protected)
```

## Authentication

### Register

```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "securepass123"
}
```

Response `201`:
```json
{
    "success": true,
    "message": "Register successful",
    "data": {
        "token": "eyJhbGciOiJSUzI1NiIs...",
        "refresh_token": "eyJhbGciOiJSUzI1NiIs...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

### Login

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "securepass123"
}
```

Response `200`:
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "eyJhbGciOiJSUzI1NiIs...",
        "refresh_token": "eyJhbGciOiJSUzI1NiIs...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

### Use Token

All protected endpoints require the `Authorization` header:

```http
Authorization: Bearer eyJhbGciOiJSUzI1NiIs...
```

### Refresh Token

```http
POST /api/auth/refresh
Content-Type: application/json

{
    "refresh_token": "eyJhbGciOiJSUzI1NiIs..."
}
```

Response `200`:
```json
{
    "success": true,
    "message": "Token refreshed",
    "data": {
        "token": "eyJhbGciOiJSUzI1NiIs...",
        "refresh_token": "eyJhbGciOiJSUzI1NiIs...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

## Pagination, Sorting & Filtering

### Pagination

All list endpoints support pagination via query parameters:

```http
GET /api/posts?page=2&per_page=10
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 20 | Items per page (max 100) |

Response includes pagination metadata:
```json
{
    "success": true,
    "message": "Posts list",
    "data": [...],
    "meta": {
        "current_page": 2,
        "per_page": 10,
        "total": 47,
        "last_page": 5,
        "has_more": true
    }
}
```

### Sorting

```http
GET /api/posts?sort=created_at&order=desc
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| sort | string | created_at | Field to sort by |
| order | string | desc | Sort direction (asc, desc) |

### Filtering

```http
GET /api/posts?locale=en&status=published
GET /api/posts?locale=vi
```

## Post CRUD Examples

### List Posts

```http
GET /api/posts?page=1&per_page=10
```

Response `200`:
```json
{
    "success": true,
    "message": "Posts list",
    "data": [
        {
            "id": 1,
            "title": "Hello World",
            "body": "This is the first post...",
            "locale": "en",
            "status": "published",
            "image": "/storage/uploads/posts/image.jpg",
            "created_at": "2026-05-15T10:00:00Z",
            "updated_at": "2026-05-15T10:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 1,
        "last_page": 1,
        "has_more": false
    }
}
```

### Get Post

```http
GET /api/posts/1
```

Response `200`:
```json
{
    "success": true,
    "message": "Post detail",
    "data": {
        "id": 1,
        "title": "Hello World",
        "body": "This is the first post...",
        "locale": "en",
        "status": "published",
        "image": "/storage/uploads/posts/image.jpg",
        "created_at": "2026-05-15T10:00:00Z",
        "updated_at": "2026-05-15T10:00:00Z"
    }
}
```

### Create Post

```http
POST /api/posts
Authorization: Bearer eyJhbGciOiJSUzI1NiIs...
Content-Type: application/json

{
    "title": "My New Post",
    "body": "This is the content of my new post. It must be at least 10 characters long.",
    "locale": "en",
    "status": "draft"
}
```

Response `201`:
```json
{
    "success": true,
    "message": "Post created",
    "data": {
        "id": 2,
        "title": "My New Post",
        "body": "This is the content of my new post. It must be at least 10 characters long.",
        "locale": "en",
        "status": "draft",
        "image": null,
        "created_at": "2026-05-15T12:00:00Z",
        "updated_at": "2026-05-15T12:00:00Z"
    }
}
```

### Create Post with Image

```http
POST /api/posts
Authorization: Bearer eyJhbGciOiJSUzI1NiIs...
Content-Type: multipart/form-data

title: "Post with Image"
body: "Content with an uploaded image..."
locale: "en"
status: "published"
image: @/path/to/image.jpg
```

### Update Post

```http
PUT /api/posts/2
Authorization: Bearer eyJhbGciOiJSUzI1NiIs...
Content-Type: application/json

{
    "title": "Updated Title",
    "status": "published"
}
```

Response `200`:
```json
{
    "success": true,
    "message": "Post updated",
    "data": {
        "id": 2,
        "title": "Updated Title",
        "body": "This is the content of my new post...",
        "locale": "en",
        "status": "published",
        "created_at": "2026-05-15T12:00:00Z",
        "updated_at": "2026-05-15T13:00:00Z"
    }
}
```

### Delete Post

```http
DELETE /api/posts/2
Authorization: Bearer eyJhbGciOiJSUzI1NiIs...
```

Response `204` (No Content)

## Error Responses

### Validation Error (422)

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "title": ["The title field is required."],
        "body": ["The body must be at least 10 characters."]
    }
}
```

### Not Found (404)

```json
{
    "success": false,
    "message": "Post not found"
}
```

### Unauthorized (401)

```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### Forbidden (403)

```json
{
    "success": false,
    "message": "Forbidden"
}
```

## Pagination Examples

### Page Through Results

```http
# First page
GET /api/posts?page=1&per_page=5

# Second page
GET /api/posts?page=2&per_page=5

# Last page
GET /api/posts?page=10&per_page=5
```

### Filter by Locale and Status

```http
GET /api/posts?locale=en&status=published&page=1&per_page=20
```

### Sort by Different Fields

```http
# Newest first (default)
GET /api/posts?sort=created_at&order=desc

# Oldest first
GET /api/posts?sort=created_at&order=asc

# Alphabetical by title
GET /api/posts?sort=title&order=asc
```

## Complete Workflow Example

```bash
# 1. Register
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"secret123"}' | jq .

# Save token
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"secret123"}' | jq -r '.data.token')

# 2. Create a post
curl -X POST http://localhost:8080/api/posts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"My First Post","body":"This is the body content. It must be at least 10 characters.","locale":"en","status":"published"}' | jq .

# 3. List all posts
curl -s http://localhost:8080/api/posts | jq .

# 4. Get single post
curl -s http://localhost:8080/api/posts/1 | jq .

# 5. Update post
curl -X PUT http://localhost:8080/api/posts/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Updated Title"}' | jq .

# 6. Delete post
curl -X DELETE http://localhost:8080/api/posts/1 \
  -H "Authorization: Bearer $TOKEN"

# 7. List tags
curl -s http://localhost:8080/api/tags | jq .

# 8. Create tag
curl -X POST http://localhost:8080/api/tags \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"php"}' | jq .

# 9. List categories
curl -s http://localhost:8080/api/categories | jq .

# 10. Create category
curl -X POST http://localhost:8080/api/categories \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Technology"}' | jq .

# 11. Paginated list
curl -s "http://localhost:8080/api/posts?page=1&per_page=5&sort=created_at&order=desc" | jq .

# 12. Health check
curl -s http://localhost:8080/health | jq .
```

## Relations

- A **Post** belongs to a **User** (author)
- A **Post** has many **Comments**
- A **Post** belongs to many **Tags** (pivot table)
- A **Post** belongs to a **Category**
- A **Comment** belongs to a **Post** and a **User**

## Rate Limiting

| Endpoint | Limit |
|----------|-------|
| POST /api/auth/login | 60 requests/minute |
| POST /api/auth/register | 30 requests/minute |
| POST /api/auth/forgot-password | 10 requests/minute |
| Protected CRUD endpoints | 60 requests/minute |
| Public GET endpoints | 120 requests/minute |
