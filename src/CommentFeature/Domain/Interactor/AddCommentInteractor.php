<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Exception\CommentDeletedException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;

final class AddCommentInteractor
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function add(
        CommentableType $entityType,
        string $entityId,
        string $authorId,
        CommentContent $content,
        ?CommentId $parentId = null,
    ): Comment {
        if ($parentId !== null) {
            $this->guardParentBelongsToEntity($parentId, $entityType, $entityId);
        }

        $comment = Comment::create(
            CommentId::generate(),
            $entityType,
            $entityId,
            $authorId,
            $content,
            $this->clock->now(),
            $parentId,
        );

        $this->comments->save($comment);
        $this->eventDispatcher->dispatch(...$comment->pullDomainEvents());

        return $comment;
    }

    private function guardParentBelongsToEntity(
        CommentId $parentId,
        CommentableType $entityType,
        string $entityId,
    ): void {
        $parent = $this->comments->findById($parentId);

        if (
            $parent === null
            || $parent->entityType()->value() !== $entityType->value()
            || $parent->entityId() !== $entityId
        ) {
            throw CommentNotFoundException::withId($parentId->value());
        }

        if ($parent->isDeleted()) {
            throw CommentDeletedException::cannotReplyTo($parentId->value());
        }
    }
}
