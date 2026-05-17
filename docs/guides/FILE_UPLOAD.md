# File Upload Guide

## Receiving Files

Use `$request->file()` to access uploaded files:

```php
use Siro\Core\Request;
use Siro\Core\Response;

// In a controller or route handler
public function upload(Request $request): Response
{
    $file = $request->file('avatar');

    if ($file === null || !$file->isValid()) {
        return Response::error('No file uploaded', 422);
    }

    $path = $file->store('avatars');

    return Response::success([
        'path' => $path,
        'original_name' => $file->getClientOriginalName(),
        'size' => $file->getSize(),
        'mime' => $file->getMimeType(),
    ], 'File uploaded');
}
```

### UploadedFile Methods

| Method | Description |
|--------|-------------|
| `$file->isValid()` | Check if upload was successful |
| `$file->store('path')` | Store file, returns stored path |
| `$file->getClientOriginalName()` | Original filename from client |
| `$file->getSize()` | File size in bytes |
| `$file->getMimeType()` | MIME type (e.g. `image/jpeg`) |

### Route Example

```php
$router->post('/upload/avatar', function (Request $req): Response {
    $file = $req->file('avatar');
    if ($file === null || !$file->isValid()) {
        return Response::error('No file uploaded', 422);
    }
    $path = $file->store('avatars');
    return Response::success([
        'path' => $path,
        'original_name' => $file->getClientOriginalName(),
        'size' => $file->getSize(),
        'mime' => $file->getMimeType(),
    ], 'Avatar uploaded');
})->middleware([JsonMiddleware::class]);
```

## Validation Rules for Files

Validate file uploads using `$request->validate()`:

```php
$request->validate([
    'avatar' => 'required',            // File must be present
    'document' => 'mimes:pdf,doc',     // Restrict MIME types
]);
```

| Rule | Description |
|------|-------------|
| `required` | File must be uploaded |
| `mimes:pdf,jpg,png` | Only allow specific MIME types |

## Storage Drivers

### Local Storage (default)

Files are stored under `storage/app/`:

```env
STORAGE_DRIVER=local
STORAGE_PATH=storage/app
```

```bash
# Create public storage symlink
php siro storage:link
```

### S3 Storage

```env
STORAGE_DRIVER=s3
STORAGE_S3_KEY=your-key
STORAGE_S3_SECRET=your-secret
STORAGE_S3_REGION=us-east-1
STORAGE_S3_BUCKET=my-bucket
STORAGE_S3_ENDPOINT=   # Optional (for MinIO, DigitalOcean Spaces)
```

## File Download

Send files to clients:

```php
use Siro\Core\Response;

// Download (forces download dialog)
return Response::download($path, 'filename.pdf');

// Display inline
return Response::file($path, 'application/pdf');
```

## Security

### MIME Type Validation

Always validate MIME types to prevent malicious uploads:

```php
$request->validate([
    'photo' => 'mimes:jpg,png,gif,webp',
]);
```

### File Size

Check size server-side (upload limits also enforced by `php.ini`):

```php
// In PHP config
upload_max_filesize = 10M
post_max_size = 12M

// Server-side check
$file = $request->file('document');
if ($file->getSize() > 10 * 1024 * 1024) {
    return Response::error('File too large', 422);
}
```

### Path Traversal Prevention

The `store()` method sanitizes filenames and prevents directory traversal.
Always use the returned path rather than constructing paths from user input:

```php
// Safe
$path = $file->store('uploads');

// UNSAFE — never do this
$path = 'uploads/' . $request->string('filename');
```

### Additional Measures

- Store uploaded files outside the web root when possible.
- Serve files through a controller rather than directly exposing the storage directory.
- Scan uploads for malware in production environments.
- Use random filenames to prevent enumeration attacks.
