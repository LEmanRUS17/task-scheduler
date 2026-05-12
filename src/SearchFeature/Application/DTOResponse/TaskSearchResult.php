<?php

declare(strict_types=1);

namespace App\SearchFeature\Application\DTOResponse;

use App\SearchFeatureApi\DTOResponse\TaskSearchResultInterface;

final class TaskSearchResult implements TaskSearchResultInterface
{
    public function __construct(
        private readonly string $taskId,
        private readonly string $title,
        private readonly string $status,
    ) {}

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
