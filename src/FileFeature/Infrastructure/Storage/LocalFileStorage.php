<?php

declare(strict_types=1);

namespace App\FileFeature\Infrastructure\Storage;

use App\FileFeature\Domain\Port\FileStorageInterface;

final class LocalFileStorage implements FileStorageInterface
{
    private readonly string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function store(string $tmpPath, string $relativePath): void
    {
        $target = $this->absolutePath($relativePath);
        $this->ensureDirectory(dirname($target));

        // rename() also works for uploaded temp files and keeps the adapter testable
        // outside of an HTTP request (move_uploaded_file would reject non-uploaded files).
        if (!@rename($tmpPath, $target)) {
            if (!@copy($tmpPath, $target)) {
                throw new \RuntimeException("Unable to store file at: {$target}");
            }

            @unlink($tmpPath);
        }
    }

    public function writeContents(string $contents, string $relativePath): void
    {
        $target = $this->absolutePath($relativePath);
        $this->ensureDirectory(dirname($target));

        if (@file_put_contents($target, $contents) === false) {
            throw new \RuntimeException("Unable to write file at: {$target}");
        }
    }

    public function delete(string $relativePath): void
    {
        $target = $this->absolutePath($relativePath);

        if (is_file($target)) {
            @unlink($target);
        }
    }

    public function absolutePath(string $relativePath): string
    {
        return $this->basePath . '/' . ltrim($relativePath, '/');
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException("Unable to create storage directory: {$directory}");
        }
    }
}
