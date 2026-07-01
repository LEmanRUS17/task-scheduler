<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Interactor;

use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Port\ClockInterface;
use App\TagFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;

final class CreateTagInteractor
{
    public function __construct(
        private readonly TagRepositoryInterface $tags,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function create(string $ownerId, TagName $name, TagColor $color): Tag
    {
        if ($this->tags->findByOwnerAndName($ownerId, $name->value()) !== null) {
            throw new \DomainException(sprintf('Tag "%s" already exists', $name->value()));
        }

        $tag = Tag::create(TagId::generate(), $ownerId, $name, $color, $this->clock->now());

        $this->tags->save($tag);
        $this->eventDispatcher->dispatch(...$tag->pullDomainEvents());

        return $tag;
    }
}
