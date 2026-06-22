<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\Entity;

use App\FileFeature\Domain\ValueObject\FilePurpose;

final class StoredFile
{
    private string $id;
    private string $entityClass;
    private string $entityId;
    private string $purpose;
    private string $originalName;
    private string $storagePath;
    private string $mimeType;
    private int $size;
    private string $uploadedBy;
    private \DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $entityClass,
        string $entityId,
        FilePurpose $purpose,
        string $originalName,
        string $storagePath,
        string $mimeType,
        int $size,
        string $uploadedBy,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->purpose = $purpose->value;
        $this->originalName = $originalName;
        $this->storagePath = $storagePath;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->uploadedBy = $uploadedBy;
        $this->createdAt = $createdAt;
    }

    public static function create(
        string $id,
        string $entityClass,
        string $entityId,
        FilePurpose $purpose,
        string $originalName,
        string $storagePath,
        string $mimeType,
        int $size,
        string $uploadedBy,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            $id,
            $entityClass,
            $entityId,
            $purpose,
            $originalName,
            $storagePath,
            $mimeType,
            $size,
            $uploadedBy,
            $createdAt,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function entityClass(): string
    {
        return $this->entityClass;
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function purpose(): FilePurpose
    {
        return FilePurpose::fromString($this->purpose);
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function storagePath(): string
    {
        return $this->storagePath;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function uploadedBy(): string
    {
        return $this->uploadedBy;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
