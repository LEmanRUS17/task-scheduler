<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface WorkflowSearchRepositoryInterface
{
    /**
     * @return array<int, array{workflowId: string, title: string}>
     */
    public function search(string $query, string $userId, bool $ownedOnly): array;
}
