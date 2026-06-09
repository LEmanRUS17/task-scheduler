<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Application\ApiService;

use App\TaskFeature\Application\ApiService\TaskApiService;
use App\TaskFeature\Application\DataMapper\TaskDataMapper;
use App\TaskFeature\Application\DTORequestValidator\TaskValidatorInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Interactor\AddTaskAssigneeInteractor;
use App\TaskFeature\Domain\Interactor\ApplyTaskTransitionInteractor;
use App\TaskFeature\Domain\Interactor\CreateTaskInteractor;
use App\TaskFeature\Domain\Interactor\RemoveTaskAssigneeInteractor;
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
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class TaskApiServiceTest extends TestCase
{
    private function buildService(
        TaskRepositoryInterface $tasks,
        TaskAssigneeRepositoryInterface $assignees,
        TeamMembershipInterface $teamMembership,
        ?TaskWorkflowInterface $workflow = null,
    ): TaskApiService {
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock = $this->createStub(ClockInterface::class);
        $workflow ??= $this->createStub(TaskWorkflowInterface::class);

        return new TaskApiService(
            new CreateTaskInteractor($tasks, $assignees, $dispatcher, $clock, $workflow),
            new UpdateTaskInteractor($tasks, $dispatcher),
            new ApplyTaskTransitionInteractor(
                $tasks,
                $workflow,
                $this->createStub(WorkflowTransitionRepositoryInterface::class),
                $dispatcher,
            ),
            new AddTaskAssigneeInteractor($tasks, $assignees, $teamMembership, $dispatcher, $clock),
            new RemoveTaskAssigneeInteractor($tasks, $assignees, $dispatcher),
            $tasks,
            $assignees,
            $this->createStub(TaskStatusHistoryRepositoryInterface::class),
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
            $this->createStub(ProfileServiceInterface::class),
            new TaskDataMapper(),
            $this->createStub(TaskValidatorInterface::class),
            $dispatcher,
            $workflow,
            $teamMembership,
        );
    }

    private function makeTask(string $teamId = 'team-1'): Task
    {
        return Task::create(
            TaskId::fromString('a1b2c3d4-e5f6-4789-8abc-def012345678'),
            TaskTitle::fromString('Test Task'),
            TaskPriority::NORMAL,
            'default',
            $teamId,
            'user-creator',
            new \DateTimeImmutable('2026-01-01 12:00:00'),
        );
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
}
