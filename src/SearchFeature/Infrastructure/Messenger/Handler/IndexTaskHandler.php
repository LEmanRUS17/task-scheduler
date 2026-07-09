<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\Handler;

use App\SearchFeature\Domain\Port\TaskSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTaskMessage;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class IndexTaskHandler
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly TaskSearchIndexInterface $searchIndex,
        private readonly TagServiceInterface $tagService,
    ) {
    }

    public function __invoke(IndexTaskMessage $message): void
    {
        $task = $this->taskService->getById($message->taskId);

        // TODO: when the task no longer exists, remove it from the index instead
        //       of returning (see IndexTagHandler for the same pattern).
        if ($task === null) {
            return;
        }

        $tagNames = $this->tagService->getEntityTagNames(TagServiceInterface::TYPE_TASK, $task->getId());
        $tags = implode(' ', $tagNames);

        $this->searchIndex->index(
            $task->getId(),
            $task->getTitle(),
            $task->getPriority(),
            $task->getStatus(),
            $task->getTeamId(),
            $task->getCreatedBy(),
            $tags,
        );
    }
}
