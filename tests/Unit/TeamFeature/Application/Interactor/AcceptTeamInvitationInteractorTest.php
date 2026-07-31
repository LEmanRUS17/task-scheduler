<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Interactor\AcceptTeamInvitationInteractor;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamInvitationRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamInvitationId;
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

    private function makeInvitation(
        string $token = 'valid-token',
        string $invitedUserId = 'user-1',
        \DateTimeImmutable $expiresAt = new \DateTimeImmutable('2024-01-08 12:00:00'),
    ): TeamInvitation {
        return TeamInvitation::create(
            TeamInvitationId::generate(),
            $this->teamId,
            'Backend',
            $invitedUserId,
            'invitee@example.com',
            'owner-1',
            TeamMemberRole::MEMBER,
            $token,
            new \DateTimeImmutable('2024-01-01 12:00:00'),
            $expiresAt,
        );
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

    public function testAcceptCreatesMemberAndDispatchesEvent(): void
    {
        $invitation = $this->makeInvitation();

        $invitations = $this->createMock(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn($invitation);
        $invitations->expects($this->once())->method('save');

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
        $invitations = $this->createStub(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($invitations, $this->createStub(TeamMemberRepositoryInterface::class))
            ->accept('unknown-token', 'user-1');
    }

    public function testAcceptThrowsWhenInvitationBelongsToAnotherUser(): void
    {
        $invitation = $this->makeInvitation(invitedUserId: 'user-1');

        $invitations = $this->createStub(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn($invitation);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($invitations, $this->createStub(TeamMemberRepositoryInterface::class))
            ->accept('valid-token', 'someone-else');
    }

    public function testAcceptThrowsWhenAlreadyMember(): void
    {
        $invitation = $this->makeInvitation();
        $existingMember = TeamMember::add($this->teamId, 'user-1', TeamMemberRole::MEMBER, new \DateTimeImmutable());

        $invitations = $this->createStub(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn($invitation);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn($existingMember);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($invitations, $members)->accept('valid-token', 'user-1');
    }

    public function testAcceptThrowsWhenExpired(): void
    {
        $invitation = $this->makeInvitation(expiresAt: new \DateTimeImmutable('2024-01-01 13:00:00'));

        $invitations = $this->createStub(TeamInvitationRepositoryInterface::class);
        $invitations->method('findByToken')->willReturn($invitation);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($invitations, $members)->accept('valid-token', 'user-1');
    }
}
