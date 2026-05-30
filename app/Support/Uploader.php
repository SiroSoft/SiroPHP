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
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'zip', 'csv'];
    private const MAX_SIZE = 10 * 1024 * 1024; // 10MB
    private const MIME_MAP = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf', 'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'zip' => 'application/zip', 'csv' => 'text/csv',
    ];

    /**
     * Handle a file upload from a request.
     *
     * @param Request $request The current request
     * @param string $field The form field name (e.g. 'file', 'avatar', 'image')
     * @param string $directory Subdirectory in storage/public/ (e.g. 'avatars', 'products', 'posts')
     * @return array{error: bool, response?: Response, url?: string, path?: string}
     */
    public static function handle(Request $request, string $field = 'file', string $directory = 'uploads'): array
    {
        $file = $request->file($field);

        if ($file === null || !$file->isValid()) {
            return [
                'error' => true,
                'response' => Response::error("No file uploaded for field '{$field}'", 422),
            ];
        }

        // Size check
        if ($file->getSize() > self::MAX_SIZE) {
            $maxMB = self::MAX_SIZE / 1024 / 1024;
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
        $expectedMime = self::MIME_MAP[$ext] ?? null;
        $actualMime = $file->getMimeType();
        if ($expectedMime !== null && $actualMime !== null && !str_starts_with($actualMime, $expectedMime)) {
            return [
                'error' => true,
                'response' => Response::error("File content does not match extension. Expected {$expectedMime}, got {$actualMime}", 422),
            ];
        }

        try {
            $path = $file->store($directory);
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
                    'mime' => $actualMime ?: $expectedMime ?: 'application/octet-stream',
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
     * Quick upload handler for route closures.
     *
     * Usage in routes/api.php:
     *   $router->post('/upload', function (Request $req) {
     *       return Uploader::response($req, 'file', 'uploads');
     *   })->middleware(['auth']);
     */
    public static function response(Request $request, string $field = 'file', string $directory = 'uploads'): Response
    {
        $result = self::handle($request, $field, $directory);
        return $result['response'];
    }
}
