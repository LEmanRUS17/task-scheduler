<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Event\TeamDeleted;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

final class TeamDeleteInteractor
{
    public function __construct(
        private readonly TeamRepositoryInterface $team,
        private readonly TeamMemberRepositoryInterface $teamMember,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function delete(string $id, string $userId): bool
    {
        $team = $this->team->findById(TeamId::fromString($id));

        if ($team === null) {
            throw new \DomainException("Team {$id} not found");
        }

        $team = $this->teamMember->findByTeamAndUserAndRole(
            TeamId::fromString($id),
            $userId,
            TeamMemberRole::OWNER
        );

        if (is_null($team)) {
            throw new \DomainException("The team {$id} has not been deleted");
        }

        $this->eventDispatcher->dispatch(new TeamDeleted($id));

        return (bool) $team;
    }
}
