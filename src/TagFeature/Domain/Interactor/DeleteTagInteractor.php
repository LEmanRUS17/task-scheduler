<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Interactor;

use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;

final class DeleteTagInteractor
{
    public function __construct(
        private readonly TagRepositoryInterface $tags,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function delete(Tag $tag): void
    {
        $tag->markDeleted();

        $events = $tag->pullDomainEvents();
        $this->tags->delete($tag);
        $this->eventDispatcher->dispatch(...$events);
    }
}
