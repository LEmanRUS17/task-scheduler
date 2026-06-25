<?php

declare(strict_types=1);

namespace App\FileFeature\Application\DataMapper;

use App\FileFeature\Domain\Entity\StoredFile;
use App\FileFeatureApi\Contract\FileMetadataInterface;

final class FileMetadataResponse implements FileMetadataInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $originalName,
        private readonly string $mimeType,
        private readonly int $size,
        private readonly string $purpose,
        private readonly string $entityClass,
        private readonly string $entityId,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromEntity(StoredFile $file): self
    {
        return new self(
            $file->id(),
            $file->originalName(),
            $file->mimeType(),
            $file->size(),
            $file->purpose()->value,
            $file->entityClass(),
            $file->entityId(),
            $file->createdAt(),
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
