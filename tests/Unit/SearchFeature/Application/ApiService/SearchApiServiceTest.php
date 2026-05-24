<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Application\ApiService;

use App\SearchFeature\Application\ApiService\SearchApiService;
use App\SearchFeature\Application\DTOResponse\TaskSearchResult;
use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;
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

        $results = (new SearchApiService($repository))->searchTasks('fix');

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
            ->with('fix bug', 'team-1', 'open')
            ->willReturn([]);

        (new SearchApiService($repository))->searchTasks('fix bug', 'team-1', 'open');
    }

    public function testSearchTasksWithDefaultNullFilters(): void
    {
        $repository = $this->createMock(TaskSearchRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('search')
            ->with('fix', null, null)
            ->willReturn([]);

        (new SearchApiService($repository))->searchTasks('fix');
    }

    public function testSearchTasksReturnsEmptyArrayWhenRepositoryReturnsNothing(): void
    {
        $repository = $this->createStub(TaskSearchRepositoryInterface::class);
        $repository->method('search')->willReturn([]);

        $this->assertSame([], (new SearchApiService($repository))->searchTasks('nothing'));
    }
}
