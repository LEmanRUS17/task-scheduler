<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TaskSearchIndexInterface
{
    public function index(
        string $taskId,
        string $title,
        string $priority,
        string $status,
        ?string $teamId,
        string $createdBy,
        string $tags = '',
    ): void;

    public function delete(string $taskId): void;
}
