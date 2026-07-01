<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TeamSearchIndexInterface
{
    /** @param list<string> $memberIds */
    public function index(
        string $teamId,
        string $title,
        string $status,
        string $createdBy,
        \DateTimeImmutable $createdAt,
        array $memberIds,
        string $tags = '',
    ): void;

    public function delete(string $teamId): void;
}
