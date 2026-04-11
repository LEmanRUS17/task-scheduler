<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Messenger;

use App\User\Application\Port\DomainEventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerDomainEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {}

    public function dispatch(object ...$events): void
    {
        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
