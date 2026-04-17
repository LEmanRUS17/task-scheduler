<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Interactor\TeamCreateInteractor;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;

final class TeamCreateInteractorTest extends TestCase
{
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 13:00:00'));
    }

    private function buildInteractor(
        TeamRepositoryInterface $teams,
        TeamMemberRepositoryInterface $members,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): TeamCreateInteractor {
        return new TeamCreateInteractor(
            $teams,
            $members,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testCreateSavesTeam(): void
    {
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->expects($this->once())->method('save');

        $this->buildInteractor($teams, $this->createStub(TeamMemberRepositoryInterface::class))
            ->create(Title::fromString('test_team_create'), 'user-170426');
    }

    public function testCreateSavesOwnerMember(): void
    {
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->expects($this->once())->method('save');

        $this->buildInteractor($this->createStub(TeamRepositoryInterface::class), $members)
            ->create(Title::fromString('test_team_create'), 'user-170426');
    }

    public function testCreateDispatchesEvents(): void
    {
        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch');

        $this->buildInteractor(
            $this->createStub(TeamRepositoryInterface::class),
            $this->createStub(TeamMemberRepositoryInterface::class),
            $dispatcher,
        )->create(Title::fromString('test_team_create'), 'user-170426');
    }

    public function testCreateReturnsTeam(): void
    {
        $team = $this->buildInteractor(
            $this->createStub(TeamRepositoryInterface::class),
            $this->createStub(TeamMemberRepositoryInterface::class),
        )->create(Title::fromString('test_team_create'), 'user-170426');

        $this->assertInstanceOf(Team::class, $team);
        $this->assertSame('test_team_create', $team->title()->value());
    }
}
