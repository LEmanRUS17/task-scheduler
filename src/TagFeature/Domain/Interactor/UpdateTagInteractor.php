<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Interactor;

use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;

final class UpdateTagInteractor
{
    public function __construct(
        private readonly TagRepositoryInterface $tags,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function update(TagId $id, TagName $name, TagColor $color): Tag
    {
        $tag = $this->tags->findById($id);

        if ($tag === null) {
            throw new \DomainException(sprintf('Tag "%s" not found', $id->value()));
        }

        $existing = $this->tags->findByOwnerAndName($tag->ownerId(), $name->value());
        if ($existing !== null && !$existing->id()->equals($id)) {
            throw new \DomainException(sprintf('Tag "%s" already exists', $name->value()));
        }

        $tag->update($name, $color);

        $this->tags->save($tag);
        $this->eventDispatcher->dispatch(...$tag->pullDomainEvents());

        return $tag;
    }
}
