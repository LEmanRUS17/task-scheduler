<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Interactor\AddTeamMemberInteractor;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;

final class AddTeamMemberInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private TeamId $teamId;
    private Team $team;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 13:00:00'));

        $this->teamId = TeamId::generate();
        $this->team = Team::create($this->teamId, Title::fromString('test_team_create'), new \DateTimeImmutable());
    }

    private function buildInteractor(
        TeamRepositoryInterface $teams,
        TeamMemberRepositoryInterface $members,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): AddTeamMemberInteractor {
        return new AddTeamMemberInteractor(
            $teams,
            $members,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testAddSavesMember(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);
        $members->expects($this->once())->method('save');

        $this->buildInteractor($teams, $members)
            ->add($this->teamId, 'user-170426', TeamMemberRole::MEMBER);
    }

    public function testAddDispatchesEvent(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor($teams, $members, $dispatcher)
            ->add($this->teamId, 'user-170426', TeamMemberRole::MEMBER);
    }

    public function testAddReturnsMember(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);

        $member = $this->buildInteractor($teams, $members)
            ->add($this->teamId, 'user-170426', TeamMemberRole::MEMBER);

        $this->assertInstanceOf(TeamMember::class, $member);
        $this->assertSame($this->teamId->value(), $member->teamId()->value());
        $this->assertSame('user-170426', $member->userId());
        $this->assertSame(TeamMemberRole::MEMBER, $member->role());
    }

    public function testAddThrowsWhenTeamNotFound(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($teams, $this->createStub(TeamMemberRepositoryInterface::class))
            ->add($this->teamId, 'user-170426', TeamMemberRole::MEMBER);
    }

    public function testAddThrowsWhenAlreadyMember(): void
    {
        $existingMember = TeamMember::add($this->teamId, 'user-170426', TeamMemberRole::MEMBER, new \DateTimeImmutable());

        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn($existingMember);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($teams, $members)
            ->add($this->teamId, 'user-170426', TeamMemberRole::MEMBER);
    }
}
