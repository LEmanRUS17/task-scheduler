<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\Message\IndexTagMessage;
use App\TagFeature\Domain\Event\TagCreated;
use App\TagFeature\Domain\Event\TagDeleted;
use App\TagFeature\Domain\Event\TagUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class TagSearchIndexSubscriber
{
    public function __construct(private readonly MessageBusInterface $defaultBus)
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTagCreated(TagCreated $event): void
    {
        $this->defaultBus->dispatch(new IndexTagMessage($event->id->value()));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTagUpdated(TagUpdated $event): void
    {
        $this->defaultBus->dispatch(new IndexTagMessage($event->id->value()));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTagDeleted(TagDeleted $event): void
    {
        $this->defaultBus->dispatch(new IndexTagMessage($event->id->value()));
    }
}
