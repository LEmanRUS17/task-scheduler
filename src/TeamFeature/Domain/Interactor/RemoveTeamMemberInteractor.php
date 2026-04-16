<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;

final class RemoveTeamMemberInteractor
{
    public function __construct(
        private readonly TeamMemberRepositoryInterface $members,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function remove(TeamId $teamId, string $userId): void
    {
        $member = $this->members->findByTeamAndUser($teamId, $userId);

        if ($member === null) {
            throw new \DomainException("User {$userId} is not a member of team {$teamId->value()}");
        }

        $this->members->delete($member);
    }
}
