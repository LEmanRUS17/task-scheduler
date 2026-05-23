<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\Handler;

use App\SearchFeature\Domain\Port\TaskSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Handler\IndexTaskHandler;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTaskMessage;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use PHPUnit\Framework\TestCase;

final class IndexTaskHandlerTest extends TestCase
{
    private function makeTask(
        string $id = 'task-uuid',
        string $title = 'Fix bug',
        string $priority = 'normal',
        string $status = 'open',
        ?string $teamId = 'team-1',
        string $createdBy = 'user-1',
    ): TaskDataResponseInterface {
        $task = $this->createStub(TaskDataResponseInterface::class);
        $task->method('getId')->willReturn($id);
        $task->method('getTitle')->willReturn($title);
        $task->method('getPriority')->willReturn($priority);
        $task->method('getStatus')->willReturn($status);
        $task->method('getTeamId')->willReturn($teamId);
        $task->method('getCreatedBy')->willReturn($createdBy);

        return $task;
    }

    public function testIndexesTaskWithAllFieldsWhenFound(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask());

        $searchIndex = $this->createMock(TaskSearchIndexInterface::class);
        $searchIndex->expects($this->once())
            ->method('index')
            ->with('task-uuid', 'Fix bug', 'normal', 'open', 'team-1', 'user-1');

        (new IndexTaskHandler($taskService, $searchIndex))(new IndexTaskMessage('task-uuid'));
    }

    public function testIndexesTaskWithNullTeamId(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask(teamId: null));

        $searchIndex = $this->createMock(TaskSearchIndexInterface::class);
        $searchIndex->expects($this->once())
            ->method('index')
            ->with('task-uuid', 'Fix bug', 'normal', 'open', null, 'user-1');

        (new IndexTaskHandler($taskService, $searchIndex))(new IndexTaskMessage('task-uuid'));
    }

    public function testDoesNothingWhenTaskNotFound(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn(null);

        $searchIndex = $this->createMock(TaskSearchIndexInterface::class);
        $searchIndex->expects($this->never())->method('index');

        (new IndexTaskHandler($taskService, $searchIndex))(new IndexTaskMessage('task-uuid'));
    }
}
