<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\ApiService;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TeamFeature\Application\ApiService\TeamApiService;
use App\TeamFeature\Application\DataMapper\TeamDataMapper;
use App\TeamFeature\Application\DTORequestValidator\TeamValidatorInterface;
use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Interactor\AddTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\RemoveTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\TeamCreateInteractor;
use App\TeamFeature\Domain\Interactor\TeamUpdateInteractor;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;

final class TeamApiServiceTest extends TestCase
{
    public function testGetPagePassesUserLimitAndOffsetToRepository(): void
    {
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->expects($this->once())
            ->method('findPaginatedByMemberUserId')
            ->with('user-7', 20, 40)
            ->willReturn([]);

        $this->assertSame([], $this->makeService($teams)->getPage('user-7', 20, 40));
    }

    public function testGetPageMapsTeams(): void
    {
        $team = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');

        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findPaginatedByMemberUserId')->willReturn([$team]);

        $result = $this->makeService($teams)->getPage('user-1', 10, 0);

        $this->assertCount(1, $result);
        $this->assertSame($team->id()->value(), $result[0]->getId());
        $this->assertSame('Backend', $result[0]->getTitle());
    }

    public function testCountAllDelegatesToRepository(): void
    {
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->expects($this->once())
            ->method('countByMemberUserId')
            ->with('user-7')
            ->willReturn(42);

        $this->assertSame(42, $this->makeService($teams)->countAll('user-7'));
    }

    public function testGetByIdsPreservesIdOrderRegardlessOfRepositoryOrder(): void
    {
        $t1 = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');
        $t2 = $this->makeTeam('22222222-2222-4222-8222-222222222222', 'Frontend');
        $t3 = $this->makeTeam('33333333-3333-4333-8333-333333333333', 'Platform');

        // Repository returns them in a different (e.g. DB) order.
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findByIds')->willReturn([$t3, $t1, $t2]);

        $ids = [$t1->id()->value(), $t2->id()->value(), $t3->id()->value()];
        $result = $this->makeService($teams)->getByIds($ids);

        $this->assertSame($ids, array_map(static fn($r) => $r->getId(), $result));
        $this->assertSame('Backend', $result[0]->getTitle());
    }

    public function testGetByIdsSkipsMissingTeams(): void
    {
        $t1 = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');

        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findByIds')->willReturn([$t1]);

        $result = $this->makeService($teams)->getByIds([$t1->id()->value(), 'missing-id']);

        $this->assertCount(1, $result);
        $this->assertSame($t1->id()->value(), $result[0]->getId());
    }

    public function testGetByIdsWithEmptyListReturnsEmpty(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findByIds')->willReturn([]);

        $this->assertSame([], $this->makeService($teams)->getByIds([]));
    }

    private function makeTeam(string $id, string $title): Team
    {
        return Team::create(
            TeamId::fromString($id),
            Title::fromString($title),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
    }

    private function makeService(TeamRepositoryInterface $teams): TeamApiService
    {
        // Interactors are final and cannot be doubled; build real ones with stubbed ports.
        // The paginated/getByIds reads do not touch them, so their wiring is irrelevant here.
        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock = $this->createStub(ClockInterface::class);

        return new TeamApiService(
            new TeamCreateInteractor($teams, $members, $dispatcher, $clock),
            new TeamUpdateInteractor($teams, $dispatcher),
            new AddTeamMemberInteractor($teams, $members, $dispatcher, $clock),
            new RemoveTeamMemberInteractor($members, $dispatcher),
            $teams,
            $members,
            new TeamDataMapper(),
            $this->createStub(TeamValidatorInterface::class),
            $this->createStub(DescriptionServiceInterface::class),
        );
    }
}
