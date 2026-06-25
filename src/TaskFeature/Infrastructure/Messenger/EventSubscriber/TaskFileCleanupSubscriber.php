<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Messenger\EventSubscriber;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskDeleted;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class TaskFileCleanupSubscriber
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {
    }

    /**
     * Remove every file attached to a task once the task itself is deleted,
     * so no orphaned database rows or stored files are left behind.
     */
    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskDeleted(TaskDeleted $event): void
    {
        $this->fileService->deleteAttachments(Task::class, $event->taskId);
    }
}
