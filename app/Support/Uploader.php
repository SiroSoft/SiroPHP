<?php

declare(strict_types=1);

namespace App\Support;

use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\UploadedFile;
use Siro\Core\Env;

/**
 * Enterprise file upload helper.
 *
 * Usage:
 *   $result = Uploader::handle($request, 'file', 'products');
 *   if ($result['error']) return $result['response'];
 *   // $result['url'] is the full public URL
 */
final class Uploader
{
    // Keep in sync with Siro\Core\UploadedFile::ALLOWED_EXTENSIONS
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'csv', 'json', 'xml
    private const DEFAULT_MAX_MB = 10;

    private static function maxBytes(): int
    {
        // Single source of truth via env; must stay <= core MAX_BODY_SIZE_MB
        // and PHP upload_max_filesize/post_max_size (see .env.example).
        $mb = (int) Env::get('UPLOAD_MAX_MB', (string) self::DEFAULT_MAX_MB);
        return max(1, $mb) * 1024 * 1024;
    }
    private const MIME_MAP = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf', 'txt' => 'text/plain', 'csv' => 'text/csv',
        'json' => 'application/json', 'xml' => 'application/xml',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'zip' => 'application/zip',
    ];

    /**
     * Handle a file upload from a request.
     *
     * Files are organized by date: {type}/YYYY/MM/{uuid}.{ext}
     * This prevents directory bloat and enables easy cleanup of old files.
     *
     * @param Request $request The current request
     * @param string $field The form field name (e.g. 'file', 'avatar', 'image')
     * @param string $type Subdirectory type (e.g. 'uploads', 'avatars', 'products', 'posts')
     * @return array{error: bool, response?: Response, url?: string, path?: string}
     */
    public static function handle(Request $request, string $field = 'file', string $type = 'uploads'): array
    {
        $file = $request->file($field);

        if ($file === null || !$file->isValid()) {
            return [
                'error' => true,
                'response' => Response::error("No file uploaded for field '{$field}'", 422),
            ];
        }

        // Size check
        $maxBytes = self::maxBytes();
        if ($file->getSize() > $maxBytes) {
            $maxMB = $maxBytes / 1024 / 1024;
            return [
                'error' => true,
                'response' => Response::error("File too large. Maximum {$maxMB}MB allowed.", 422),
            ];
        }

        // Extension + MIME validation
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return [
                'error' => true,
                'response' => Response::error("File type '{$ext}' not allowed. Allowed: " . implode(', ', self::ALLOWED_EXTENSIONS), 422),
            ];
        }

        // Verify MIME matches extension
        $expectedMime = self::MIME_MAP[$ext];
        $actualMime = $file->getMimeType();
        if (!str_starts_with($actualMime, $expectedMime)) {
            return [
                'error' => true,
                'response' => Response::error("File content does not match extension. Expected {$expectedMime}, got {$actualMime}", 422),
            ];
        }

        try {
            // Enterprise directory structure: {type}/YYYY/MM/
            $dateDir = date('Y') . '/' . date('m');
            $fullDir = $type . '/' . $dateDir;
            // UploadedFile::store() already returns a web path with the
            // /storage prefix (storage/public exposed via public/storage
            // symlink — php siro storage:link). Do NOT prefix again.
            $path = $file->store($fullDir);
            $baseUrl = rtrim((string) Env::get('APP_URL', 'http://localhost:8080'), '/');
            $cleanPath = '/' . ltrim($path, '/');

            return [
                'error' => false,
                'url' => $baseUrl . $cleanPath,
                'path' => $cleanPath,
                'response' => Response::success([
                    'path' => $cleanPath,
                    'url' => $baseUrl . $cleanPath,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $actualMime,
                ], 'File uploaded', 201),
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'response' => Response::error('Upload failed: ' . $e->getMessage(), 500),
            ];
        }
    }

    /**
     * Quick upload handler for route closures. One-liner.
     *
     * Usage: $router->post('/upload', fn(Request $r) => Uploader::response($r, 'file', 'uploads'));
     */
    public static function response(Request $request, string $field = 'file', string $type = 'uploads'): Response
    {
        $result = self::handle($request, $field, $type);
        return $result['response'] ?? Response::error('Upload failed', 500);
    }
}
