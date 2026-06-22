<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\Port;

interface FileStorageInterface
{
    /**
     * Move an uploaded temporary file into permanent storage under $relativePath.
     *
     * @throws \RuntimeException when the file cannot be stored
     */
    public function store(string $tmpPath, string $relativePath): void;

    public function delete(string $relativePath): void;

    public function absolutePath(string $relativePath): string;
}
