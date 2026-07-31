<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamInvitationRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;

final class AcceptTeamInvitationInteractor
{
    public function __construct(
        private readonly TeamInvitationRepositoryInterface $invitations,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function accept(string $token, string $userId): TeamMember
    {
        $invitation = $this->invitations->findByToken($token);

        if ($invitation === null) {
            throw new \DomainException('Invitation not found');
        }

        if ($invitation->invitedUserId() !== $userId) {
            throw new \DomainException('This invitation does not belong to you');
        }

        if ($this->members->findByTeamAndUser($invitation->teamId(), $userId) !== null) {
            throw new \DomainException("User {$userId} is already a member of team {$invitation->teamId()->value()}");
        }

        $now = $this->clock->now();
        $invitation->accept($token, $now);
        $this->invitations->save($invitation);

        $member = TeamMember::add($invitation->teamId(), $userId, $invitation->role(), $now);
        $this->members->save($member);
        $this->eventDispatcher->dispatch(...$member->pullDomainEvents());

        return $member;
    }
}
