<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Interactor;

use App\TagFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TagFeature\Domain\Repository\TagAssignmentRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TaggableType;

final class UnassignTagInteractor
{
    public function __construct(
        private readonly TagAssignmentRepositoryInterface $assignments,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function unassign(TagId $tagId, TaggableType $entityType, string $entityId): void
    {
        $assignment = $this->assignments->find($tagId, $entityType, $entityId);

        if ($assignment === null) {
            return;
        }

        $assignment->markUnassigned();

        $events = $assignment->pullDomainEvents();
        $this->assignments->delete($assignment);
        $this->eventDispatcher->dispatch(...$events);
    }
}
