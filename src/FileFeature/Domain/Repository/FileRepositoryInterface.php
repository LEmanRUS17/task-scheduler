<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\Repository;

use App\FileFeature\Domain\Entity\StoredFile;

interface FileRepositoryInterface
{
    public function findById(string $id): ?StoredFile;

    public function findAvatar(string $entityClass, string $entityId): ?StoredFile;

    /** @return list<StoredFile> */
    public function findAttachments(string $entityClass, string $entityId): array;

    public function save(StoredFile $file): void;

    public function delete(StoredFile $file): void;
}
