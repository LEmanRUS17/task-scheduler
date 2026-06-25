<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Infrastructure\Messenger\EventSubscriber;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskDeleted;
use App\TaskFeature\Infrastructure\Messenger\EventSubscriber\TaskFileCleanupSubscriber;
use PHPUnit\Framework\TestCase;

final class TaskFileCleanupSubscriberTest extends TestCase
{
    public function testDeletesEveryAttachmentOfTheDeletedTask(): void
    {
        $fileService = $this->createMock(FileServiceInterface::class);
        $fileService->expects($this->once())
            ->method('deleteAttachments')
            ->with(Task::class, 'task-123', null);

        $subscriber = new TaskFileCleanupSubscriber($fileService);

        $subscriber->onTaskDeleted(new TaskDeleted('task-123'));
    }
}
