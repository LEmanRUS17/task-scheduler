<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Event\TeamGet;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;

final class TeamGetInteractor
{
    public function __construct(
        private readonly TeamMemberRepositoryInterface $teamMember,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function get(string $id, string $userId): bool
    {
        $team = $this->teamMember->findByTeamAndUser(TeamId::fromString($id), $userId);

        if (is_null($team)) {
            throw new \DomainException("Team {$id} not found");
        }

        $this->eventDispatcher->dispatch(new TeamGet($id));

        return (bool) $team;
    }
}
