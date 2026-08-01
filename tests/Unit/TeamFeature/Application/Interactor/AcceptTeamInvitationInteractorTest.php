<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Interactor\AcceptTeamInvitationInteractor;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamInvitationRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\PendingTeamInvitation;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use PHPUnit\Framework\TestCase;

final class AcceptTeamInvitationInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private TeamId $teamId;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-02 13:00:00'));

        $this->teamId = TeamId::generate();
    }

    private function makePendingInvitation(string $invitedUserId = 'user-1'): PendingTeamInvitation
    {
        return new PendingTeamInvitation($this->teamId, $invitedUserId, TeamMemberRole::MEMBER);
    }

    private function buildInteractor(
        TeamInvitationRepositoryInterface $invitations,
        TeamMemberRepositoryInterface $members,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): AcceptTeamInvitationInteractor {
        return new AcceptTeamInvitationInteractor(
            $invitations,
            $members,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testAcceptCreatesMemberAndDeletesInvitation(): void
    {
        $invitations = $this->createMock(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn($this->makePendingInvitation());
        $invitations->expects($this->once())->method('delete')->with('valid-token', $this->teamId, 'user-1');

        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);
        $members->expects($this->once())->method('save');

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $member = $this->buildInteractor($invitations, $members, $dispatcher)
            ->accept('valid-token', 'user-1');

        $this->assertInstanceOf(TeamMember::class, $member);
        $this->assertSame($this->teamId->value(), $member->teamId()->value());
        $this->assertSame('user-1', $member->userId());
        $this->assertSame(TeamMemberRole::MEMBER, $member->role());
    }

    public function testAcceptThrowsWhenInvitationNotFound(): void
    {
        $invitations = $this->createMock(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn(null);
        $invitations->expects($this->never())->method('delete');

        $this->expectException(\DomainException::class);

        $this->buildInteractor($invitations, $this->createStub(TeamMemberRepositoryInterface::class))
            ->accept('unknown-token', 'user-1');
    }

    public function testAcceptThrowsWhenInvitationBelongsToAnotherUser(): void
    {
        $invitations = $this->createMock(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn($this->makePendingInvitation(invitedUserId: 'user-1'));
        $invitations->expects($this->never())->method('delete');

        $this->expectException(\DomainException::class);

        $this->buildInteractor($invitations, $this->createStub(TeamMemberRepositoryInterface::class))
            ->accept('valid-token', 'someone-else');
    }

    public function testAcceptThrowsWhenAlreadyMember(): void
    {
        $existingMember = TeamMember::add($this->teamId, 'user-1', TeamMemberRole::MEMBER, new \DateTimeImmutable());

        $invitations = $this->createMock(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn($this->makePendingInvitation());
        $invitations->expects($this->never())->method('delete');

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn($existingMember);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($invitations, $members)->accept('valid-token', 'user-1');
    }
}
