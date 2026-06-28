<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\Message\IndexTaskMessage;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTeamMessage;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexWorkflowMessage;
use App\TagFeature\Domain\Event\TagAssigned;
use App\TagFeature\Domain\Event\TagUnassigned;
use App\TagFeatureApi\Contract\TagServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class TagSearchIndexSubscriber
{
    public function __construct(private readonly MessageBusInterface $defaultBus)
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTagAssigned(TagAssigned $event): void
    {
        $this->reindex($event->entityType->value(), $event->entityId);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTagUnassigned(TagUnassigned $event): void
    {
        $this->reindex($event->entityType->value(), $event->entityId);
    }

    private function reindex(string $entityType, string $entityId): void
    {
        $message = match ($entityType) {
            TagServiceInterface::TYPE_TASK => new IndexTaskMessage($entityId),
            TagServiceInterface::TYPE_TEAM => new IndexTeamMessage($entityId),
            TagServiceInterface::TYPE_WORKFLOW => new IndexWorkflowMessage($entityId),
            default => null,
        };

        if ($message !== null) {
            $this->defaultBus->dispatch($message);
        }
    }
}
