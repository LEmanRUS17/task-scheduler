<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Interactor;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeature\Domain\ValueObject\Title;

final class TeamCreateInteractor
{
    public function __construct(
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function create(Title $title, string $creatorUserId): Team
    {
        $id = TeamId::generate();
        $now = $this->clock->now();

        $team = Team::create($id, $title, $now);
        $this->teams->save($team);
        $this->eventDispatcher->dispatch(...$team->pullDomainEvents());

        $owner = TeamMember::add($id, $creatorUserId, TeamMemberRole::OWNER, $now);
        $this->members->save($owner);
        $this->eventDispatcher->dispatch(...$owner->pullDomainEvents());

        return $team;
    }
}
