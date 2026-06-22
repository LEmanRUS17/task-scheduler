<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\EventSubscriber;

use App\NotificationFeature\Infrastructure\Messenger\Message\UserRegisteredMessage;
use App\UserFeature\Domain\Event\UserRegistered;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserNotificationSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onUserRegistered(UserRegistered $event): void
    {
        $this->defaultBus->dispatch(new UserRegisteredMessage(
            userId: $event->userId->value(),
            email: $event->email->value(),
            confirmationCode: $event->confirmationCode,
        ));
    }
}
