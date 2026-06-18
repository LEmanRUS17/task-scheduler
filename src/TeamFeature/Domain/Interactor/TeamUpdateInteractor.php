<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Event\TeamUpdated;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;

final class TeamUpdateInteractor
{
    public function __construct(
        private readonly TeamRepositoryInterface $teams,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function update(string $id): Team
    {
        $team = $this->teams->findById(TeamId::fromString($id));

        if ($team === null) {
            throw new \DomainException("Team {$id} not found");
        }

        $this->eventDispatcher->dispatch(new TeamUpdated($id));

        return $team;
    }
}
