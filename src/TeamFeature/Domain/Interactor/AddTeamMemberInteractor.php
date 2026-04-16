<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

final class AddTeamMemberInteractor
{
    public function __construct(
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {}

    public function add(TeamId $teamId, string $userId, TeamMemberRole $role): TeamMember
    {
        if ($this->teams->findById($teamId) === null) {
            throw new \DomainException("Team {$teamId->value()} not found");
        }

        if ($this->members->findByTeamAndUser($teamId, $userId) !== null) {
            throw new \DomainException("User {$userId} is already a member of team {$teamId->value()}");
        }

        $member = TeamMember::add(
            $teamId,
            $userId,
            $role,
            $this->clock->now(),
        );

        $this->members->save($member);
        $this->eventDispatcher->dispatch(...$member->pullDomainEvents());

        return $member;
    }
}
