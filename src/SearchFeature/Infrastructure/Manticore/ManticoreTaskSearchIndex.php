<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\TaskSearchIndexInterface;

final class ManticoreTaskSearchIndex implements TaskSearchIndexInterface
{
    public function __construct(private readonly ManticoreClient $client) {}

    public function index(string $taskId, string $title, string $priority, string $status, ?string $teamId, string $createdBy): void
    {
        $this->client->replace('tasks', $this->numericId($taskId), [
            'task_id' => $taskId,
            'title' => $title,
            'priority' => $priority,
            'status' => $status,
            'team_id' => $teamId ?? '',
            'created_by' => $createdBy,
        ]);
    }

    public function delete(string $taskId): void
    {
        $this->client->delete('tasks', $this->numericId($taskId));
    }

    private function numericId(string $taskId): int
    {
        return (int) hexdec(substr(sha1($taskId), 0, 15));
    }
}
