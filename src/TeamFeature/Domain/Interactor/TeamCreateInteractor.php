<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\Title;

final class TeamCreateInteractor
{
    public function __construct(
        private readonly TeamRepositoryInterface $teams,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {}

    public function create(Title $title): Team
    {
        $id = TeamId::generate();
        $team = Team::create($id, $title, $this->clock->now());

        $this->teams->save($team);

        $this->eventDispatcher->dispatch(...$team->pullDomainEvents());

        return $team;
    }
}
