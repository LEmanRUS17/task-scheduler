<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\Handler;

use App\SearchFeature\Domain\Port\TaskSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTaskMessage;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class IndexTaskHandler
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly TaskSearchIndexInterface $searchIndex,
    ) {
    }

    public function __invoke(IndexTaskMessage $message): void
    {
        $task = $this->taskService->getById($message->taskId);

        if ($task === null) {
            return;
        }

        $this->searchIndex->index(
            $task->getId(),
            $task->getTitle(),
            $task->getPriority(),
            $task->getStatus(),
            $task->getTeamId(),
            $task->getCreatedBy(),
        );
    }
}
