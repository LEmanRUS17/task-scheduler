<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Application\ApiService;

use App\TaskFeature\Application\ApiService\TaskApiService;
use App\TaskFeature\Application\DataMapper\TaskDataMapper;
use App\TaskFeature\Application\DTORequest\TaskCreateRequestDTO;
use App\TaskFeature\Application\DTORequestValidator\TaskValidatorInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Interactor\AddTaskAssigneeInteractor;
use App\TaskFeature\Domain\Interactor\ApplyTaskTransitionInteractor;
use App\TaskFeature\Domain\Interactor\CloseTaskInteractor;
use App\TaskFeature\Domain\Interactor\CreateTaskInteractor;
use App\TaskFeature\Domain\Interactor\RemoveTaskAssigneeInteractor;
use App\TaskFeature\Domain\Interactor\ReopenTaskInteractor;
use App\TaskFeature\Domain\Interactor\UpdateTaskInteractor;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Port\TeamMembershipInterface;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskStatusHistoryRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\CommentFeatureApi\Contract\CommentServiceInterface;
use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class TaskApiServiceTest extends TestCase
{
    private function buildService(
        TaskRepositoryInterface $tasks,
        TaskAssigneeRepositoryInterface $assignees,
        TeamMembershipInterface $teamMembership,
        ?TaskWorkflowInterface $workflow = null,
        ?TaskValidatorInterface $validator = null,
        ?TagServiceInterface $tagService = null,
        ?ClockInterface $clock = null,
        ?CommentServiceInterface $commentService = null,
    ): TaskApiService {
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock ??= $this->createStub(ClockInterface::class);
        $workflow ??= $this->createStub(TaskWorkflowInterface::class);
        $validator ??= $this->createStub(TaskValidatorInterface::class);
        $tagService ??= $this->createStub(TagServiceInterface::class);

        return new TaskApiService(
            new CreateTaskInteractor($tasks, $assignees, $dispatcher, $clock, $workflow),
            new UpdateTaskInteractor($tasks, $dispatcher),
            new ApplyTaskTransitionInteractor(
                $tasks,
                $workflow,
                $this->createStub(WorkflowTransitionRepositoryInterface::class),
                $dispatcher,
            ),
            new CloseTaskInteractor($tasks, $dispatcher, $clock),
            new ReopenTaskInteractor($tasks, $this->createStub(WorkflowStatusRepositoryInterface::class), $dispatcher),
            new AddTaskAssigneeInteractor($tasks, $assignees, $teamMembership, $dispatcher, $clock),
            new RemoveTaskAssigneeInteractor($tasks, $assignees, $dispatcher),
            $tasks,
            $assignees,
            $this->createStub(TaskStatusHistoryRepositoryInterface::class),
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
            $this->createStub(WorkflowStatusRepositoryInterface::class),
            $this->createStub(ProfileServiceInterface::class),
            new TaskDataMapper(),
            $validator,
            $dispatcher,
            $workflow,
            $teamMembership,
            $this->createStub(DescriptionServiceInterface::class),
            $tagService,
            $commentService ?? $this->createStub(CommentServiceInterface::class),
            $clock,
        );
    }

    private function makeTask(
        string $teamId = 'team-1',
        string $id = 'a1b2c3d4-e5f6-4789-8abc-def012345678',
        string $title = 'Test Task',
    ): Task {
        return Task::create(
            TaskId::fromString($id),
            TaskTitle::fromString($title),
            TaskPriority::NORMAL,
            'default',
            $teamId,
            'user-creator',
            new \DateTimeImmutable('2026-01-01 12:00:00'),
        );
    }

    // --- getPage / countAll ---

    public function testGetPagePassesUserLimitAndOffsetToRepository(): void
    {
        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->expects($this->once())
            ->method('findPaginatedByAssigneeUserId')
            ->with('user-7', 20, 40)
            ->willReturn([]);

        $result = $this->buildService(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $this->createStub(TeamMembershipInterface::class),
        )->getPage('user-7', 20, 40);

        $this->assertSame([], $result);
    }

    public function testGetPageMapsTasks(): void
    {
        $task = $this->makeTask('team-1');

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findPaginatedByAssigneeUserId')->willReturn([$task]);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([]);

        $result = $this->buildService(
            $tasks,
            $assignees,
            $this->createStub(TeamMembershipInterface::class),
        )->getPage('user-1', 10, 0);

        $this->assertCount(1, $result);
        $this->assertSame('a1b2c3d4-e5f6-4789-8abc-def012345678', $result[0]->getId());
    }

    public function testCountAllDelegatesToRepository(): void
    {
        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->expects($this->once())
            ->method('countByAssigneeUserId')
            ->with('user-7')
            ->willReturn(42);

        $result = $this->buildService(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $this->createStub(TeamMembershipInterface::class),
        )->countAll('user-7');

        $this->assertSame(42, $result);
    }

    // --- getByIds ---

    public function testGetByIdsPreservesIdOrderRegardlessOfRepositoryOrder(): void
    {
        $t1 = $this->makeTask('team-1', '11111111-1111-4111-8111-111111111111', 'First');
        $t2 = $this->makeTask('team-1', '22222222-2222-4222-8222-222222222222', 'Second');
        $t3 = $this->makeTask('team-1', '33333333-3333-4333-8333-333333333333', 'Third');

        // Repository returns them in a different (e.g. DB) order.
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findByIds')->willReturn([$t3, $t1, $t2]);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([]);

        $ids = [$t1->id()->value(), $t2->id()->value(), $t3->id()->value()];
        $result = $this->buildService(
            $tasks,
            $assignees,
            $this->createStub(TeamMembershipInterface::class),
        )->getByIds($ids);

        $this->assertSame($ids, array_map(static fn($r) => $r->getId(), $result));
        $this->assertSame('First', $result[0]->getTitle());
    }

    public function testGetByIdsSkipsMissingTasks(): void
    {
        $t1 = $this->makeTask('team-1', '11111111-1111-4111-8111-111111111111', 'First');

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findByIds')->willReturn([$t1]);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([]);

        $result = $this->buildService(
            $tasks,
            $assignees,
            $this->createStub(TeamMembershipInterface::class),
        )->getByIds([$t1->id()->value(), 'missing-id']);

        $this->assertCount(1, $result);
        $this->assertSame($t1->id()->value(), $result[0]->getId());
    }

    public function testGetByIdsWithEmptyListReturnsEmpty(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findByIds')->willReturn([]);

        $result = $this->buildService(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $this->createStub(TeamMembershipInterface::class),
        )->getByIds([]);

        $this->assertSame([], $result);
    }

    // --- getListByTeam ---

    public function testGetListByTeamThrowsWhenUserIsNotMember(): void
    {
        $teamMembership = $this->createStub(TeamMembershipInterface::class);
        $teamMembership->method('isMember')->willReturn(false);

        $this->expectException(\DomainException::class);

        $this->buildService(
            $this->createStub(TaskRepositoryInterface::class),
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $teamMembership,
        )->getListByTeam('team-1', 'user-1');
    }

    public function testGetListByTeamDoesNotQueryRepositoryWhenUserIsNotMember(): void
    {
        $teamMembership = $this->createStub(TeamMembershipInterface::class);
        $teamMembership->method('isMember')->willReturn(false);

        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->expects($this->never())->method('findByTeamId');

        try {
            $this->buildService(
                $tasks,
                $this->createStub(TaskAssigneeRepositoryInterface::class),
                $teamMembership,
            )->getListByTeam('team-1', 'user-1');
        } catch (\DomainException) {
        }
    }

    public function testGetListByTeamReturnsEmptyArrayWhenTeamHasNoTasks(): void
    {
        $teamMembership = $this->createStub(TeamMembershipInterface::class);
        $teamMembership->method('isMember')->willReturn(true);

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findByTeamId')->willReturn([]);

        $result = $this->buildService(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $teamMembership,
        )->getListByTeam('team-1', 'user-1');

        $this->assertSame([], $result);
    }

    public function testGetListByTeamReturnsMappedTasksForMember(): void
    {
        $teamMembership = $this->createStub(TeamMembershipInterface::class);
        $teamMembership->method('isMember')->willReturn(true);

        $task = $this->makeTask('team-1');

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findByTeamId')->willReturn([$task]);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([]);

        $result = $this->buildService($tasks, $assignees, $teamMembership)->getListByTeam('team-1', 'user-1');

        $this->assertCount(1, $result);
        $this->assertSame('a1b2c3d4-e5f6-4789-8abc-def012345678', $result[0]->getId());
        $this->assertSame('Test Task', $result[0]->getTitle());
        $this->assertSame('team-1', $result[0]->getTeamId());
    }

    public function testGetListByTeamPassesTeamIdToRepository(): void
    {
        $teamMembership = $this->createStub(TeamMembershipInterface::class);
        $teamMembership->method('isMember')->willReturn(true);

        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->expects($this->once())
            ->method('findByTeamId')
            ->with('team-42')
            ->willReturn([]);

        $this->buildService(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $teamMembership,
        )->getListByTeam('team-42', 'user-1');
    }

    public function testGetListByTeamChecksCorrectTeamAndUser(): void
    {
        $teamMembership = $this->createMock(TeamMembershipInterface::class);
        $teamMembership->expects($this->once())
            ->method('isMember')
            ->with('team-42', 'user-99')
            ->willReturn(true);

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findByTeamId')->willReturn([]);

        $this->buildService(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $teamMembership,
        )->getListByTeam('team-42', 'user-99');
    }

    // --- deleteById ---

    public function testDeleteByIdRemovesTaskComments(): void
    {
        $task = $this->makeTask();

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($task);

        $commentService = $this->createMock(CommentServiceInterface::class);
        $commentService->expects($this->once())
            ->method('deleteEntityComments')
            ->with('task', $task->id()->value());

        $this->buildService(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $this->createStub(TeamMembershipInterface::class),
            null,
            null,
            null,
            null,
            $commentService,
        )->deleteById($task->id()->value());
    }

    // --- create with tags ---

    public function testCreateAssignsEachProvidedTagToTheNewTask(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));

        $validator = $this->createStub(TaskValidatorInterface::class);
        $validator->method('validate')->willReturn([]);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->method('filterExistingTagIds')->willReturn(['tag-1', 'tag-2']);

        $assignedTagIds = [];
        $tagService->expects($this->exactly(2))
            ->method('assign')
            ->willReturnCallback(
                function (
                    string $tagId,
                    string $entityType,
                    string $entityId,
                    string $assignedBy,
                ) use (&$assignedTagIds): void {
                    $this->assertSame(TagServiceInterface::TYPE_TASK, $entityType);
                    $this->assertSame('user-creator', $assignedBy);
                    $this->assertNotSame('', $entityId);
                    $assignedTagIds[] = $tagId;
                },
            );

        $service = $this->buildService(
            $tasks,
            $assignees,
            $this->createStub(TeamMembershipInterface::class),
            null,
            $validator,
            $tagService,
            $clock,
        );

        $request = new TaskCreateRequestDTO(
            title: 'Tagged task',
            workflow: 'default',
            tagIds: ['tag-1', 'tag-2'],
        );

        $service->create($request, 'user-creator');

        $this->assertSame(['tag-1', 'tag-2'], $assignedTagIds);
    }

    public function testCreateRejectsUnknownTagIdsAndDoesNotPersistTask(): void
    {
        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->expects($this->never())->method('save');

        $validator = $this->createStub(TaskValidatorInterface::class);
        $validator->method('validate')->willReturn([]);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->method('filterExistingTagIds')->willReturn(['tag-1']);
        $tagService->expects($this->never())->method('assign');

        $service = $this->buildService(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $this->createStub(TeamMembershipInterface::class),
            null,
            $validator,
            $tagService,
        );

        $request = new TaskCreateRequestDTO(
            title: 'Tagged task',
            workflow: 'default',
            tagIds: ['tag-1', 'missing-tag'],
        );

        try {
            $service->create($request, 'user-creator');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('missing-tag', $e->getMessage());
        }
    }
}
