<?php

declare(strict_types=1);

namespace App\Tests\Unit\AuditLogFeature\Application\ApiService;

use App\AuditLogFeature\Application\ApiService\AuditLogApiService;
use App\AuditLogFeature\Domain\Entity\AuditEntry;
use App\AuditLogFeature\Domain\Repository\AuditEntryRepositoryInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use PHPUnit\Framework\TestCase;

final class AuditLogApiServiceTest extends TestCase
{
    private const TASK_CLASS = 'App\TaskFeature\Domain\Entity\Task';

    public function testGetMyActivityExposesEventTypeForTaskCompletion(): void
    {
        $entry = AuditEntry::record(
            'entry-id-1',
            self::TASK_CLASS,
            'task-id-1',
            'update',
            [
                'workflowStatus' => ['in_progress', 'done'],
                'closedAt' => [null, '2024-05-01T10:00:00+00:00'],
            ],
            'actor-id-1',
            new \DateTimeImmutable('2024-05-01 10:00:00'),
        );

        $repository = $this->createStub(AuditEntryRepositoryInterface::class);
        $repository->method('findByActor')->willReturn([$entry]);
        $repository->method('countByActor')->willReturn(1);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getStatusLabelsByIds')->willReturn([]);

        $service = new AuditLogApiService($repository, $workflowService);

        $result = $service->getMyActivity('actor-id-1', null, null, 20, 0);

        self::assertSame('task_completed', $result['entries'][0]->getEventType());
    }

    public function testGetMyActivityLeavesEventTypeNullForUnclassifiedEntry(): void
    {
        $entry = AuditEntry::record(
            'entry-id-2',
            self::TASK_CLASS,
            'task-id-2',
            'update',
            ['workflowStatus' => ['todo', 'in_progress']],
            'actor-id-1',
            new \DateTimeImmutable('2024-05-01 10:00:00'),
        );

        $repository = $this->createStub(AuditEntryRepositoryInterface::class);
        $repository->method('findByActor')->willReturn([$entry]);
        $repository->method('countByActor')->willReturn(1);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getStatusLabelsByIds')->willReturn([]);

        $service = new AuditLogApiService($repository, $workflowService);

        $result = $service->getMyActivity('actor-id-1', null, null, 20, 0);

        self::assertNull($result['entries'][0]->getEventType());
    }

    public function testGetMyActivityReplacesWorkflowStatusIdsWithLabels(): void
    {
        $entry = AuditEntry::record(
            'entry-id-3',
            self::TASK_CLASS,
            'task-id-3',
            'update',
            ['workflowStatus' => ['status-id-todo', 'status-id-done']],
            'actor-id-1',
            new \DateTimeImmutable('2024-05-01 10:00:00'),
        );

        $repository = $this->createStub(AuditEntryRepositoryInterface::class);
        $repository->method('findByActor')->willReturn([$entry]);
        $repository->method('countByActor')->willReturn(1);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects(self::once())
            ->method('getStatusLabelsByIds')
            ->with(self::callback(
                static fn (array $ids): bool => sort($ids) && $ids === ['status-id-done', 'status-id-todo'],
            ))
            ->willReturn(['status-id-todo' => 'To Do', 'status-id-done' => 'Done']);

        $service = new AuditLogApiService($repository, $workflowService);

        $result = $service->getMyActivity('actor-id-1', null, null, 20, 0);

        self::assertSame(
            ['To Do', 'Done'],
            $result['entries'][0]->getChangedData()['workflowStatus'],
        );
    }

    public function testGetMyActivityKeepsRawIdWhenLabelIsMissing(): void
    {
        $entry = AuditEntry::record(
            'entry-id-4',
            self::TASK_CLASS,
            'task-id-4',
            'update',
            ['workflowStatus' => ['status-id-todo', 'status-id-deleted']],
            'actor-id-1',
            new \DateTimeImmutable('2024-05-01 10:00:00'),
        );

        $repository = $this->createStub(AuditEntryRepositoryInterface::class);
        $repository->method('findByActor')->willReturn([$entry]);
        $repository->method('countByActor')->willReturn(1);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getStatusLabelsByIds')->willReturn(['status-id-todo' => 'To Do']);

        $service = new AuditLogApiService($repository, $workflowService);

        $result = $service->getMyActivity('actor-id-1', null, null, 20, 0);

        self::assertSame(
            ['To Do', 'status-id-deleted'],
            $result['entries'][0]->getChangedData()['workflowStatus'],
        );
    }
}
