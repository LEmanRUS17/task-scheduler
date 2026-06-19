<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Application\ApiService;

use App\SearchFeature\Application\ApiService\SearchApiService;
use App\SearchFeature\Application\DTOResponse\TaskSearchResult;
use App\SearchFeature\Application\DTOResponse\TeamSearchResult;
use App\SearchFeature\Application\DTOResponse\WorkflowSearchResult;
use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\TeamSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\WorkflowSearchRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SearchApiServiceTest extends TestCase
{
    public function testSearchTasksMapsRowsToTaskSearchResult(): void
    {
        $repository = $this->createStub(TaskSearchRepositoryInterface::class);
        $repository->method('search')->willReturn([
            ['taskId' => 'task-1', 'title' => 'Fix bug',   'status' => 'open'],
            ['taskId' => 'task-2', 'title' => 'Add tests', 'status' => 'done'],
        ]);

        $results = (new SearchApiService($repository, $this->teamRepositoryStub(), $this->workflowRepositoryStub()))->searchTasks('fix', 'user-1');

        $this->assertCount(2, $results);
        $this->assertInstanceOf(TaskSearchResult::class, $results[0]);
        $this->assertSame('task-1', $results[0]->getTaskId());
        $this->assertSame('Fix bug', $results[0]->getTitle());
        $this->assertSame('open', $results[0]->getStatus());
        $this->assertSame('task-2', $results[1]->getTaskId());
    }

    public function testSearchTasksPassesParametersToRepository(): void
    {
        $repository = $this->createMock(TaskSearchRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('search')
            ->with('fix bug', 'user-1', 'team-1', 'open')
            ->willReturn([]);

        $service = new SearchApiService($repository, $this->teamRepositoryStub(), $this->workflowRepositoryStub());
        $service->searchTasks('fix bug', 'user-1', 'team-1', 'open');
    }

    public function testSearchTasksWithDefaultNullFilters(): void
    {
        $repository = $this->createMock(TaskSearchRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('search')
            ->with('fix', 'user-1', null, null)
            ->willReturn([]);

        (new SearchApiService($repository, $this->teamRepositoryStub(), $this->workflowRepositoryStub()))->searchTasks('fix', 'user-1');
    }

    public function testSearchTasksReturnsEmptyArrayWhenRepositoryReturnsNothing(): void
    {
        $repository = $this->createStub(TaskSearchRepositoryInterface::class);
        $repository->method('search')->willReturn([]);

        $service = new SearchApiService($repository, $this->teamRepositoryStub(), $this->workflowRepositoryStub());
        $this->assertSame([], $service->searchTasks('nothing', 'user-1'));
    }

    public function testSearchTeamsMapsRowsToTeamSearchResult(): void
    {
        $teamRepository = $this->createStub(TeamSearchRepositoryInterface::class);
        $teamRepository->method('search')->willReturn([
            ['teamId' => 'team-1', 'title' => 'Backend',  'status' => 'active'],
            ['teamId' => 'team-2', 'title' => 'Frontend', 'status' => 'active'],
        ]);

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub());
        $results = $service->searchTeams('end', 'user-1');

        $this->assertCount(2, $results);
        $this->assertInstanceOf(TeamSearchResult::class, $results[0]);
        $this->assertSame('team-1', $results[0]->getTeamId());
        $this->assertSame('Backend', $results[0]->getTitle());
        $this->assertSame('active', $results[0]->getStatus());
        $this->assertSame('team-2', $results[1]->getTeamId());
    }

    public function testSearchTeamsPassesParametersToRepository(): void
    {
        $teamRepository = $this->createMock(TeamSearchRepositoryInterface::class);
        $teamRepository->expects($this->once())
            ->method('search')
            ->with('backend', 'user-1', ['active', 'archived'], true)
            ->willReturn([]);

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub());
        $service->searchTeams('backend', 'user-1', ['active', 'archived'], true);
    }

    public function testSearchTeamsWithDefaultFilters(): void
    {
        $teamRepository = $this->createMock(TeamSearchRepositoryInterface::class);
        $teamRepository->expects($this->once())
            ->method('search')
            ->with('backend', 'user-1', [], false)
            ->willReturn([]);

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub());
        $service->searchTeams('backend', 'user-1');
    }

    public function testSearchWorkflowsMapsRowsToWorkflowSearchResult(): void
    {
        $workflowRepository = $this->createStub(WorkflowSearchRepositoryInterface::class);
        $workflowRepository->method('search')->willReturn([
            ['workflowId' => 'wf-1', 'title' => 'Bug flow'],
            ['workflowId' => 'wf-2', 'title' => 'Release flow'],
        ]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $workflowRepository);
        $results = $service->searchWorkflows('flow', 'user-1');

        $this->assertCount(2, $results);
        $this->assertInstanceOf(WorkflowSearchResult::class, $results[0]);
        $this->assertSame('wf-1', $results[0]->getWorkflowId());
        $this->assertSame('Bug flow', $results[0]->getTitle());
        $this->assertSame('wf-2', $results[1]->getWorkflowId());
    }

    public function testSearchWorkflowsPassesParametersToRepository(): void
    {
        $workflowRepository = $this->createMock(WorkflowSearchRepositoryInterface::class);
        $workflowRepository->expects($this->once())
            ->method('search')
            ->with('flow', 'user-1', true)
            ->willReturn([]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $workflowRepository);
        $service->searchWorkflows('flow', 'user-1', true);
    }

    public function testSearchWorkflowsWithDefaultFilters(): void
    {
        $workflowRepository = $this->createMock(WorkflowSearchRepositoryInterface::class);
        $workflowRepository->expects($this->once())
            ->method('search')
            ->with('flow', 'user-1', false)
            ->willReturn([]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $workflowRepository);
        $service->searchWorkflows('flow', 'user-1');
    }

    private function taskRepositoryStub(): TaskSearchRepositoryInterface
    {
        $repository = $this->createStub(TaskSearchRepositoryInterface::class);
        $repository->method('search')->willReturn([]);

        return $repository;
    }

    private function teamRepositoryStub(): TeamSearchRepositoryInterface
    {
        $repository = $this->createStub(TeamSearchRepositoryInterface::class);
        $repository->method('search')->willReturn([]);

        return $repository;
    }

    private function workflowRepositoryStub(): WorkflowSearchRepositoryInterface
    {
        $repository = $this->createStub(WorkflowSearchRepositoryInterface::class);
        $repository->method('search')->willReturn([]);

        return $repository;
    }
}
