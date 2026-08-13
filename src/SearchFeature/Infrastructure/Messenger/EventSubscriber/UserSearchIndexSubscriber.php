<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\ProfileFeature\Domain\Event\ProfileCreated;
use App\ProfileFeature\Domain\Event\ProfileUpdated;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexUserMessage;
use App\TeamFeature\Domain\Event\TeamMemberAdded;
use App\TeamFeature\Domain\Event\TeamMemberRemoved;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserSearchIndexSubscriber
{
    public function __construct(private readonly MessageBusInterface $defaultBus)
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onProfileCreated(ProfileCreated $event): void
    {
        $this->defaultBus->dispatch(new IndexUserMessage($event->userId));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onProfileUpdated(ProfileUpdated $event): void
    {
        $this->defaultBus->dispatch(new IndexUserMessage($event->userId));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTeamMemberAdded(TeamMemberAdded $event): void
    {
        $this->defaultBus->dispatch(new IndexUserMessage($event->userId));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTeamMemberRemoved(TeamMemberRemoved $event): void
    {
        $this->defaultBus->dispatch(new IndexUserMessage($event->userId));
    }
}
