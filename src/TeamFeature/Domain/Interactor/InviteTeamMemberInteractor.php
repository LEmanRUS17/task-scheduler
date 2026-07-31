<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamInvitationRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamInvitationId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

final class InviteTeamMemberInteractor
{
    /**
     * Invitation token lifespan.
     */
    private const string TOKEN_TTL = 'P7D';

    public function __construct(
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly TeamInvitationRepositoryInterface $invitations,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function invite(
        TeamId $teamId,
        string $invitedByUserId,
        string $invitedUserId,
        string $invitedEmail,
        TeamMemberRole $role,
    ): TeamInvitation {
        $team = $this->teams->findById($teamId);

        if ($team === null) {
            throw new \DomainException("Team {$teamId->value()} not found");
        }

        if ($this->members->findByTeamAndUser($teamId, $invitedUserId) !== null) {
            throw new \DomainException("User {$invitedUserId} is already a member of team {$teamId->value()}");
        }

        if ($this->invitations->findPendingByTeamAndUser($teamId, $invitedUserId) !== null) {
            throw new \DomainException(
                "User {$invitedUserId} already has a pending invitation to team {$teamId->value()}",
            );
        }

        $now = $this->clock->now();

        $invitation = TeamInvitation::create(
            TeamInvitationId::generate(),
            $teamId,
            $team->title()->value(),
            $invitedUserId,
            $invitedEmail,
            $invitedByUserId,
            $role,
            bin2hex(random_bytes(32)),
            $now,
            $now->add(new \DateInterval(self::TOKEN_TTL)),
        );

        $this->invitations->save($invitation);
        $this->eventDispatcher->dispatch(...$invitation->pullDomainEvents());

        return $invitation;
    }
}
