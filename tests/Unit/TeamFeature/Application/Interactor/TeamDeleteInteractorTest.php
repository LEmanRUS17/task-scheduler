<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Event\TeamDeleted;
use App\TeamFeature\Domain\Interactor\TeamDeleteInteractor;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;

final class TeamDeleteInteractorTest extends TestCase
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
        TeamMemberRepositoryInterface $members,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): TeamDeleteInteractor {
        return new TeamDeleteInteractor(
            $teams,
            $members,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
        );
    }

    private function makeOwner(): TeamMember
    {
        return TeamMember::add($this->teamId, 'owner-1', TeamMemberRole::OWNER, new \DateTimeImmutable());
    }

    public function testDeleteReturnsTrueWhenUserIsOwner(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUserAndRole')->willReturn($this->makeOwner());

        $result = $this->buildInteractor($teams, $members)->delete($this->teamId->value(), 'owner-1');

        $this->assertTrue($result);
    }

    public function testDeleteDispatchesTeamDeletedEvent(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUserAndRole')->willReturn($this->makeOwner());

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                fn(TeamDeleted $event) => $event->teamId === $this->teamId->value(),
            ));

        $this->buildInteractor($teams, $members, $dispatcher)->delete($this->teamId->value(), 'owner-1');
    }

    public function testDeleteThrowsWhenTeamNotFound(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn(null);

        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->expects($this->never())->method('findByTeamAndUserAndRole');

        $this->expectException(\DomainException::class);

        $this->buildInteractor($teams, $members)->delete($this->teamId->value(), 'owner-1');
    }

    public function testDeleteThrowsWhenUserIsNotOwner(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUserAndRole')->willReturn(null);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        $this->buildInteractor($teams, $members, $dispatcher)->delete($this->teamId->value(), 'member-1');
    }
}
