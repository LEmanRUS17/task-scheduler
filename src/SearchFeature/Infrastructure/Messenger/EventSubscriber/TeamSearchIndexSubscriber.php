<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\Message\IndexTeamMessage;
use App\TeamFeature\Domain\Event\TeamCreated;
use App\TeamFeature\Domain\Event\TeamMemberAdded;
use App\TeamFeature\Domain\Event\TeamMemberRemoved;
use App\TeamFeature\Domain\Event\TeamUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class TeamSearchIndexSubscriber
{
    public function __construct(private readonly MessageBusInterface $defaultBus)
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTeamCreated(TeamCreated $event): void
    {
        $this->defaultBus->dispatch(new IndexTeamMessage($event->id->value()));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTeamUpdated(TeamUpdated $event): void
    {
        $this->defaultBus->dispatch(new IndexTeamMessage($event->teamId));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTeamMemberAdded(TeamMemberAdded $event): void
    {
        $this->defaultBus->dispatch(new IndexTeamMessage($event->teamId->value()));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTeamMemberRemoved(TeamMemberRemoved $event): void
    {
        $this->defaultBus->dispatch(new IndexTeamMessage($event->teamId->value()));
    }
}
