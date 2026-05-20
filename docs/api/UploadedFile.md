# UploadedFile API Reference

## Overview

Handles file uploads with validation, path traversal protection, and storage.

```php
$file = $request->file('avatar');
```

---

## Basic Upload

```php
// In controller
$file = $request->file('avatar');

// Check if file is valid
if ($file === null || !$file->isValid()) {
    return Response::error('No file uploaded', 422);
}

// Store with auto-generated filename
$path = $file->store('avatars');
// "avatars/abc123.jpg"

// Store with custom name
$path = $file->storeAs('avatars', 'profile.jpg');
// "avatars/profile.jpg"
```

---

## File Info

```php
$file->getClientOriginalName();      // "photo.jpg"
$file->getClientOriginalExtension(); // "jpg"
$file->getMimeType();                // "image/jpeg"
$file->getSize();                    // 102400 (bytes)
$file->getError();                   // UPLOAD_ERR_OK (0)
$file->getPathname();                // "/tmp/phpABC123"
$file->extension();                  // "jpg"
$file->name();                       // "photo"
$file->hash();                       // "sha256hash..."
$file->isImage();                    // true
$file->isPdf();                      // false
```

---

## Type Checks

```php
// Check before processing
if ($file->isImage()) {
    $path = $file->store('images');
}

if ($file->isPdf()) {
    $path = $file->store('documents');
}

// Get MIME type (uses finfo, not client-supplied)
$mime = $file->getMimeType();  // Server-detected, cannot be spoofed
```

---

## Security

```php
// Extension whitelist (built-in)
$file->store('avatars');  // Only allows safe extensions

// Path traversal protection (built-in)
$file->store('../../etc');  // Safely rejected
```

---

## Validation Rules

```php
// In controller validation
$validated = $request->validate([
    'avatar' => 'required|file|image|max:2048',  // Max 2MB
    'document' => 'file|mimes:pdf,doc|max:10240', // Max 10MB
]);
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `isValid()` | Check if upload was successful |
| `getClientOriginalName()` | Original filename from client |
| `getClientOriginalExtension()` | Original extension |
| `getMimeType()` | Server-detected MIME type (finfo) |
| `getSize()` | File size in bytes |
| `getError()` | PHP upload error code |
| `getPathname()` | Temp file path |
| `store(directory, name)` | Store file with auto-name |
| `storeAs(directory, name)` | Store file with custom name |
| `isImage()` | Check if image type |
| `isPdf()` | Check if PDF |
| `hash()` | SHA-256 hash of file contents |
| `extension()` | File extension |
| `name()` | Filename without extension |
