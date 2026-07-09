<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Exception\CommentAccessDeniedException;
use App\CommentFeature\Domain\Exception\CommentDeletedException;
use App\CommentFeature\Domain\Exception\CommentHasRepliesException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentId;

final class DeleteCommentInteractor
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Soft-deletes the comment: the row stays in storage, but the comment is
     * marked as deleted. A comment with replies cannot be deleted.
     */
    public function delete(CommentId $id, string $authorId): void
    {
        $comment = $this->comments->findById($id);
        if ($comment === null) {
            throw CommentNotFoundException::withId($id->value());
        }

        if (!$comment->isAuthoredBy($authorId)) {
            throw CommentAccessDeniedException::notAuthor($id->value());
        }

        if ($comment->isDeleted()) {
            throw CommentDeletedException::alreadyDeleted($id->value());
        }

        if ($this->comments->hasReplies($id)) {
            throw CommentHasRepliesException::withId($id->value());
        }

        $comment->markDeleted($this->clock->now());

        $this->comments->save($comment);
        $this->eventDispatcher->dispatch(...$comment->pullDomainEvents());
    }

    public function deleteAllForEntity(CommentableType $entityType, string $entityId): void
    {
        foreach ($this->comments->findByEntity($entityType, $entityId) as $comment) {
            $comment->markDeleted($this->clock->now());

            $this->comments->delete($comment);
            $this->eventDispatcher->dispatch(...$comment->pullDomainEvents());
        }
    }
}
