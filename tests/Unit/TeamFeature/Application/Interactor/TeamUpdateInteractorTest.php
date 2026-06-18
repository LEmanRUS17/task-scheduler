<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Event\TeamUpdated;
use App\TeamFeature\Domain\Interactor\TeamUpdateInteractor;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;

final class TeamUpdateInteractorTest extends TestCase
{
    private TeamId $teamId;
    private Team $team;

    protected function setUp(): void
    {
        $this->teamId = TeamId::generate();
        $this->team = Team::create($this->teamId, Title::fromString('test_team'), new \DateTimeImmutable());
    }

    private function buildInteractor(
        TeamRepositoryInterface $teams,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): TeamUpdateInteractor {
        return new TeamUpdateInteractor(
            $teams,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
        );
    }

    public function testUpdateDispatchesTeamUpdatedEvent(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                fn(TeamUpdated $event) => $event->teamId === $this->teamId->value(),
            ));

        $this->buildInteractor($teams, $dispatcher)->update($this->teamId->value());
    }

    public function testUpdateReturnsTeam(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $result = $this->buildInteractor($teams)->update($this->teamId->value());

        $this->assertSame($this->team, $result);
    }

    public function testUpdateThrowsWhenTeamNotFound(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($teams)->update($this->teamId->value());
    }
}
