<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\EventSubscriber;

use App\NotificationFeature\Infrastructure\Messenger\Message\TeamMemberInvitedMessage;
use App\TeamFeature\Domain\Event\TeamMemberInvited;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class TeamNotificationSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTeamMemberInvited(TeamMemberInvited $event): void
    {
        $this->defaultBus->dispatch(new TeamMemberInvitedMessage(
            teamId: $event->teamId->value(),
            teamTitle: $event->teamTitle,
            invitedEmail: $event->invitedEmail,
            token: $event->token,
        ));
    }
}
