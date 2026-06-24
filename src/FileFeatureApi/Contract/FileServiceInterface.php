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

    /**
     * Absolute filesystem path of the rendered avatar for the requested size,
     * or null when the entity has no avatar. Defaults to the largest size.
     */
    public function avatarImagePath(
        string $entityClass,
        string $entityId,
        ImageSize $size = ImageSize::Large,
    ): ?string;

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

    /**
     * Validates an attachment candidate (mime/size) without storing it, so a
     * batch upload can reject the whole request before writing any file.
     *
     * @return array<string, list<string>> violations keyed by field, empty when valid
     */
    public function validateAttachment(string $mimeType, int $size): array;

    /** @return list<FileMetadataInterface> */
    public function listAttachments(string $entityClass, string $entityId): array;

    /**
     * Attachments whose content is an image (mime type "image/*").
     *
     * @return list<FileMetadataInterface>
     */
    public function listImageAttachments(string $entityClass, string $entityId): array;

    public function getFile(string $fileId): ?FileMetadataInterface;

    /** Absolute filesystem path for streaming, or null when the file is unknown. */
    public function absolutePath(string $fileId): ?string;

    public function deleteFile(string $fileId): void;
}
