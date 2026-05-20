# Storage API Reference

## Overview

Siro's storage system provides a unified API for local filesystem and S3-compatible cloud storage with path traversal protection.

```php
use Siro\Core\Storage;
```

---

## Configuration

```env
STORAGE_DRIVER=local          # local, s3
STORAGE_PATH=storage/app

# S3 (optional)
AWS_KEY=your-key
AWS_SECRET=your-secret
AWS_BUCKET=my-bucket
AWS_REGION=us-east-1
AWS_ENDPOINT=                 # custom endpoint (MinIO, DigitalOcean)
```

---

## Basic Usage

```php
// Write file
Storage::put('avatars/1.jpg', $contents);

// Read file
$contents = Storage::get('avatars/1.jpg');

// Check existence
if (Storage::exists('avatars/1.jpg')) { ... }

// Delete
Storage::delete('avatars/1.jpg');
```

---

## File Uploads

```php
// In controller
$file = $request->file('avatar');
if ($file !== null && $file->isValid()) {
    // Store with auto-generated filename
    $path = $file->store('avatars');
    // Returns: "avatars/abc123.jpg"

    // Store with custom filename
    $path = $file->storeAs('avatars', 'profile.jpg');
}
```

---

## URLs

```php
// Public URL for local files
$url = Storage::url('avatars/1.jpg');
// Returns: "/storage/avatars/1.jpg"

// Public URL for S3
$url = Storage::url('avatars/1.jpg');
// Returns: "https://bucket.s3.amazonaws.com/avatars/1.jpg"

// Temporary signed URL (S3 only)
$url = Storage::temporaryUrl('avatars/1.jpg', 3600);
```

---

## Directories

```php
// List files
$files = Storage::files('avatars');

// List with subdirectories
$allFiles = Storage::allFiles('avatars');

// List directories
$directories = Storage::directories('avatars');

// Create directory
Storage::makeDirectory('avatars/thumbs');

// Delete directory
Storage::deleteDirectory('avatars/thumbs');
```

---

## S3-Specific

```php
// Set ACL
Storage::put('public/file.txt', $contents, ['visibility' => 'public']);

// Copy between buckets
Storage::copy('bucket1/file.txt', 'bucket2/file.txt');

// Move
Storage::move('old/path.txt', 'new/path.txt');

// Get metadata
$size = Storage::size('file.txt');
$mime = Storage::mimeType('file.txt');
$lastModified = Storage::lastModified('file.txt');
```

---

## Path Traversal Protection

All file operations are protected against path traversal attacks:

```php
// These are safely rejected
Storage::get('../../../etc/passwd');
Storage::put('../../config/database.php', $data);

// Only within configured storage directory
Storage::put('avatars/photo.jpg', $contents); // ✅ OK
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `put(string $path, mixed $contents, array $options)` | Write file |
| `get(string $path)` | Read file |
| `exists(string $path)` | Check if file exists |
| `delete(string $path)` | Delete file |
| `copy(string $from, string $to)` | Copy file |
| `move(string $from, string $to)` | Move file |
| `url(string $path)` | Get public URL |
| `temporaryUrl(string $path, int $ttl)` | Get signed URL (S3) |
| `size(string $path)` | Get file size |
| `mimeType(string $path)` | Get MIME type |
| `lastModified(string $path)` | Get last modified timestamp |
| `files(string $directory)` | List files in directory |
| `allFiles(string $directory)` | List all files recursively |
| `directories(string $directory)` | List subdirectories |
| `makeDirectory(string $path)` | Create directory |
| `deleteDirectory(string $path)` | Delete directory |
