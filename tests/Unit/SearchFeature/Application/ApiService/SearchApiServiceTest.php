<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Application\ApiService;

use App\SearchFeature\Application\ApiService\SearchApiService;
use App\SearchFeature\Domain\Port\TagSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\TeamSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\UserSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\WorkflowSearchRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SearchApiServiceTest extends TestCase
{
    public function testSearchTasksReturnsIdsAndTotalFromRepository(): void
    {
        $repository = $this->createStub(TaskSearchRepositoryInterface::class);
        $repository->method('search')->willReturn(['ids' => ['task-1', 'task-2'], 'total' => 5]);

        $service = new SearchApiService($repository, $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $this->userRepositoryStub());
        $result = $service->searchTasks('fix', 'user-1');

        $this->assertSame(['ids' => ['task-1', 'task-2'], 'total' => 5], $result);
    }

    public function testSearchTasksPassesParametersToRepository(): void
    {
        $repository = $this->createMock(TaskSearchRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('search')
            ->with('fix bug', 'user-1', 'team-1', 'open', 20, 40)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($repository, $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $this->userRepositoryStub());
        $service->searchTasks('fix bug', 'user-1', 'team-1', 'open', 20, 40);
    }

    public function testSearchTasksWithDefaultParameters(): void
    {
        $repository = $this->createMock(TaskSearchRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('search')
            ->with('fix', 'user-1', null, null, 10, 0)
            ->willReturn(['ids' => [], 'total' => 0]);

        (new SearchApiService($repository, $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $this->userRepositoryStub()))->searchTasks('fix', 'user-1');
    }

    public function testSearchTasksReturnsEmptyResultWhenRepositoryReturnsNothing(): void
    {
        $repository = $this->createStub(TaskSearchRepositoryInterface::class);
        $repository->method('search')->willReturn(['ids' => [], 'total' => 0]);
        $service = new SearchApiService($repository, $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $this->userRepositoryStub());
        $this->assertSame(['ids' => [], 'total' => 0], $service->searchTasks('nothing', 'user-1'));
    }

    public function testSearchTeamsReturnsIdsAndTotalFromRepository(): void
    {
        $teamRepository = $this->createStub(TeamSearchRepositoryInterface::class);
        $teamRepository->method('search')->willReturn(['ids' => ['team-1', 'team-2'], 'total' => 5]);

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $this->userRepositoryStub());
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

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $this->userRepositoryStub());
        $service->searchTeams('backend', 'user-1', ['active', 'archived'], true, 20, 40);
    }

    public function testSearchTeamsWithDefaultParameters(): void
    {
        $teamRepository = $this->createMock(TeamSearchRepositoryInterface::class);
        $teamRepository->expects($this->once())
            ->method('search')
            ->with('backend', 'user-1', [], false, 10, 0)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $teamRepository, $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $this->userRepositoryStub());
        $service->searchTeams('backend', 'user-1');
    }

    public function testSearchWorkflowsReturnsIdsAndTotalFromRepository(): void
    {
        $workflowRepository = $this->createStub(WorkflowSearchRepositoryInterface::class);
        $workflowRepository->method('search')->willReturn(['ids' => ['wf-1', 'wf-2'], 'total' => 5]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $workflowRepository, $this->tagRepositoryStub(), $this->userRepositoryStub());
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

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $workflowRepository, $this->tagRepositoryStub(), $this->userRepositoryStub());
        $service->searchWorkflows('flow', 'user-1', true, 20, 40);
    }

    public function testSearchWorkflowsWithDefaultParameters(): void
    {
        $workflowRepository = $this->createMock(WorkflowSearchRepositoryInterface::class);
        $workflowRepository->expects($this->once())
            ->method('search')
            ->with('flow', 'user-1', false, 10, 0)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $workflowRepository, $this->tagRepositoryStub(), $this->userRepositoryStub());
        $service->searchWorkflows('flow', 'user-1');
    }

    public function testSearchTagsReturnsIdsAndTotalFromRepository(): void
    {
        $tagRepository = $this->createStub(TagSearchRepositoryInterface::class);
        $tagRepository->method('search')->willReturn(['ids' => ['tag-1', 'tag-2'], 'total' => 5]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $tagRepository, $this->userRepositoryStub());
        $result = $service->searchTags('urgent', 'user-1');

        $this->assertSame(['ids' => ['tag-1', 'tag-2'], 'total' => 5], $result);
    }

    public function testSearchTagsPassesParametersToRepository(): void
    {
        $tagRepository = $this->createMock(TagSearchRepositoryInterface::class);
        $tagRepository->expects($this->once())
            ->method('search')
            ->with('urgent', 'user-1', 20, 40)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $tagRepository, $this->userRepositoryStub());
        $service->searchTags('urgent', 'user-1', 20, 40);
    }

    public function testSearchTagsWithDefaultParameters(): void
    {
        $tagRepository = $this->createMock(TagSearchRepositoryInterface::class);
        $tagRepository->expects($this->once())
            ->method('search')
            ->with('urgent', 'user-1', 10, 0)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $tagRepository, $this->userRepositoryStub());
        $service->searchTags('urgent', 'user-1');
    }

    public function testSearchTeamUsersReturnsIdsAndTotalFromRepository(): void
    {
        $userRepository = $this->createStub(UserSearchRepositoryInterface::class);
        $userRepository->method('searchInTeam')->willReturn(['ids' => ['user-1', 'user-2'], 'total' => 5]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $userRepository);
        $result = $service->searchTeamUsers('team-1', 'ivan');

        $this->assertSame(['ids' => ['user-1', 'user-2'], 'total' => 5], $result);
    }

    public function testSearchTeamUsersPassesParametersToRepository(): void
    {
        $userRepository = $this->createMock(UserSearchRepositoryInterface::class);
        $userRepository->expects($this->once())
            ->method('searchInTeam')
            ->with('team-1', 'ivan', 20, 40)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $userRepository);
        $service->searchTeamUsers('team-1', 'ivan', 20, 40);
    }

    public function testSearchTeamUsersWithDefaultParameters(): void
    {
        $userRepository = $this->createMock(UserSearchRepositoryInterface::class);
        $userRepository->expects($this->once())
            ->method('searchInTeam')
            ->with('team-1', 'ivan', 50, 0)
            ->willReturn(['ids' => [], 'total' => 0]);

        $service = new SearchApiService($this->taskRepositoryStub(), $this->teamRepositoryStub(), $this->workflowRepositoryStub(), $this->tagRepositoryStub(), $userRepository);
        $service->searchTeamUsers('team-1', 'ivan');
    }

    private function taskRepositoryStub(): TaskSearchRepositoryInterface
    {
        $repository = $this->createStub(TaskSearchRepositoryInterface::class);
        $repository->method('search')->willReturn(['ids' => [], 'total' => 0]);

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

    private function tagRepositoryStub(): TagSearchRepositoryInterface
    {
        $repository = $this->createStub(TagSearchRepositoryInterface::class);
        $repository->method('search')->willReturn(['ids' => [], 'total' => 0]);

        return $repository;
    }

    private function userRepositoryStub(): UserSearchRepositoryInterface
    {
        $repository = $this->createStub(UserSearchRepositoryInterface::class);
        $repository->method('searchInTeam')->willReturn(['ids' => [], 'total' => 0]);

        return $repository;
    }
}
