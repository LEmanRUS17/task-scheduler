<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Interactor\InviteTeamMemberInteractor;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamInvitationRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;

final class InviteTeamMemberInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private TeamId $teamId;
    private Team $team;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 13:00:00'));

        $this->teamId = TeamId::generate();
        $this->team = Team::create($this->teamId, Title::fromString('Backend'), new \DateTimeImmutable());
    }

    private function buildInteractor(
        TeamRepositoryInterface $teams,
        TeamMemberRepositoryInterface $members,
        TeamInvitationRepositoryInterface $invitations,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): InviteTeamMemberInteractor {
        return new InviteTeamMemberInteractor(
            $teams,
            $members,
            $invitations,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    private function makeTeams(): TeamRepositoryInterface
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($this->team);

        return $teams;
    }

    public function testInviteSavesInvitationAndDispatchesEvent(): void
    {
        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);

        $invitations = $this->createMock(TeamInvitationRepositoryInterface::class);
        $invitations->method('hasPendingInvitation')->willReturn(false);
        $invitations->expects($this->once())->method('save');

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $invitation = $this->buildInteractor($this->makeTeams(), $members, $invitations, $dispatcher)
            ->invite($this->teamId, 'owner-1', 'user-1', 'invitee@example.com', TeamMemberRole::MEMBER);

        $this->assertInstanceOf(TeamInvitation::class, $invitation);
        $this->assertSame($this->teamId->value(), $invitation->teamId()->value());
        $this->assertSame('user-1', $invitation->invitedUserId());
        $this->assertSame('owner-1', $invitation->invitedByUserId());
        $this->assertSame(TeamMemberRole::MEMBER, $invitation->role());
    }

    public function testInviteThrowsWhenTeamNotFound(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor(
            $teams,
            $this->createStub(TeamMemberRepositoryInterface::class),
            $this->createStub(TeamInvitationRepositoryInterface::class),
        )->invite($this->teamId, 'owner-1', 'user-1', 'invitee@example.com', TeamMemberRole::MEMBER);
    }

    public function testInviteThrowsWhenAlreadyMember(): void
    {
        $existingMember = TeamMember::add($this->teamId, 'user-1', TeamMemberRole::MEMBER, new \DateTimeImmutable());

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn($existingMember);

        $this->expectException(\DomainException::class);

        $this->buildInteractor(
            $this->makeTeams(),
            $members,
            $this->createStub(TeamInvitationRepositoryInterface::class),
        )->invite($this->teamId, 'owner-1', 'user-1', 'invitee@example.com', TeamMemberRole::MEMBER);
    }

    public function testInviteThrowsWhenPendingInvitationAlreadyExists(): void
    {
        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);

        $invitations = $this->createStub(TeamInvitationRepositoryInterface::class);
        $invitations->method('hasPendingInvitation')->willReturn(true);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($this->makeTeams(), $members, $invitations)
            ->invite($this->teamId, 'owner-1', 'user-1', 'invitee@example.com', TeamMemberRole::MEMBER);
    }
}
