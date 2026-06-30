<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TagSearchIndexInterface
{
    public function index(
        string $tagId,
        string $name,
        string $description,
        string $ownerId,
        \DateTimeImmutable $createdAt,
    ): void;

    public function delete(string $tagId): void;
}
