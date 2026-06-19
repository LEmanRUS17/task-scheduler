<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\WorkflowSearchIndexInterface;

final class ManticoreWorkflowSearchIndex implements WorkflowSearchIndexInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    public function index(
        string $workflowId,
        string $title,
        string $description,
        string $createdBy,
        \DateTimeImmutable $createdAt,
    ): void {
        $this->client->replace('workflows', $this->numericId($workflowId), [
            'workflow_id' => $workflowId,
            'title' => $title,
            'description' => $description,
            'created_by' => $createdBy,
            'created_at' => $createdAt->getTimestamp(),
        ]);
    }

    public function delete(string $workflowId): void
    {
        $this->client->delete('workflows', $this->numericId($workflowId));
    }

    private function numericId(string $workflowId): int
    {
        return (int) hexdec(substr(sha1($workflowId), 0, 15));
    }
}
