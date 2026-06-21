<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Application\ApiService;

use App\SearchFeature\Application\ApiService\SearchApiService;
use App\SearchFeature\Application\DTOResponse\TaskSearchResult;
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

    public function testSearchTeamsReturnsIdsAndTotalFromRepository(): void
    {
        $teamRepository = $this->createStub(TeamSearchRepositoryInterface::class);
        $teamRepository->method('search')->willReturn(['ids' => ['team-1', 'team-2'], 'total' => 5]);

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub());
        $result = $service->searchTeams('end', 'user-1');

        $this->assertSame(['ids' => ['team-1', 'team-2'], 'total' => 5], $result);
    }

    public function testSearchTeamsPassesParametersToRepository(): void
    {
        $teamRepository = $this->createMock(TeamSearchRepositoryInterface::class);
        $teamRepository->expects($this->once())
            ->method('search')
            ->with('backend', 'user-1', ['active', 'archived'], true, 20, 40)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub());
        $service->searchTeams('backend', 'user-1', ['active', 'archived'], true, 20, 40);
    }

    public function testSearchTeamsWithDefaultParameters(): void
    {
        $teamRepository = $this->createMock(TeamSearchRepositoryInterface::class);
        $teamRepository->expects($this->once())
            ->method('search')
            ->with('backend', 'user-1', [], false, 10, 0)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub());
        $service->searchTeams('backend', 'user-1');
    }

    public function testSearchWorkflowsReturnsIdsAndTotalFromRepository(): void
    {
        $workflowRepository = $this->createStub(WorkflowSearchRepositoryInterface::class);
        $workflowRepository->method('search')->willReturn(['ids' => ['wf-1', 'wf-2'], 'total' => 5]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $workflowRepository);
        $result = $service->searchWorkflows('flow', 'user-1');

        $this->assertSame(['ids' => ['wf-1', 'wf-2'], 'total' => 5], $result);
    }

    public function testSearchWorkflowsPassesParametersToRepository(): void
    {
        $workflowRepository = $this->createMock(WorkflowSearchRepositoryInterface::class);
        $workflowRepository->expects($this->once())
            ->method('search')
            ->with('flow', 'user-1', true, 20, 40)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $workflowRepository);
        $service->searchWorkflows('flow', 'user-1', true, 20, 40);
    }

    public function testSearchWorkflowsWithDefaultParameters(): void
    {
        $workflowRepository = $this->createMock(WorkflowSearchRepositoryInterface::class);
        $workflowRepository->expects($this->once())
            ->method('search')
            ->with('flow', 'user-1', false, 10, 0)
            ->willReturn(['ids' => [], 'total' => 0]);

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
        $repository->method('search')->willReturn(['ids' => [], 'total' => 0]);

        return $repository;
    }

    private function workflowRepositoryStub(): WorkflowSearchRepositoryInterface
    {
        $repository = $this->createStub(WorkflowSearchRepositoryInterface::class);
        $repository->method('search')->willReturn([]);

        return $repository;
    }
}
