<?php

declare(strict_types=1);

namespace App\FileFeatureApi\Contract;

interface FileServiceInterface
{
    /**
     * Store (or replace) the single avatar attached to an entity.
     *
     * @throws \InvalidArgumentException when the file fails validation (size/mime)
     */
    public function uploadAvatar(
        string $entityClass,
        string $entityId,
        string $tmpPath,
        string $originalName,
        string $mimeType,
        int $size,
        string $uploadedBy,
    ): FileMetadataInterface;

    public function getAvatar(string $entityClass, string $entityId): ?FileMetadataInterface;

    public function deleteAvatar(string $entityClass, string $entityId): void;

    /**
     * Attach one more file to an entity (many per entity).
     *
     * @throws \InvalidArgumentException when the file fails validation (size/mime)
     */
    public function attach(
        string $entityClass,
        string $entityId,
        string $tmpPath,
        string $originalName,
        string $mimeType,
        int $size,
        string $uploadedBy,
    ): FileMetadataInterface;

    /** @return list<FileMetadataInterface> */
    public function listAttachments(string $entityClass, string $entityId): array;

    public function getFile(string $fileId): ?FileMetadataInterface;

    /** Absolute filesystem path for streaming, or null when the file is unknown. */
    public function absolutePath(string $fileId): ?string;

    public function deleteFile(string $fileId): void;
}
