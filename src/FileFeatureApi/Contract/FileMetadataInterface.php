<?php

declare(strict_types=1);

namespace App\FileFeatureApi\Contract;

interface FileMetadataInterface
{
    public function getId(): string;

    public function getOriginalName(): string;

    public function getMimeType(): string;

    public function getSize(): int;

    public function getPurpose(): string;

    public function getEntityClass(): string;

    public function getEntityId(): string;

    public function getCreatedAt(): \DateTimeImmutable;
}
