<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface WorkflowSearchIndexInterface
{
    public function index(
        string $workflowId,
        string $title,
        string $description,
        string $createdBy,
        \DateTimeImmutable $createdAt,
    ): void;

    public function delete(string $workflowId): void;
}
