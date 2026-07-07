<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Exception\CommentAccessDeniedException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentId;

final class DeleteCommentInteractor
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function delete(CommentId $id, string $authorId): void
    {
        $comment = $this->comments->findById($id);
        if ($comment === null) {
            throw CommentNotFoundException::withId($id->value());
        }

        if (!$comment->isAuthoredBy($authorId)) {
            throw CommentAccessDeniedException::notAuthor($id->value());
        }

        $this->deleteWithReplies($comment);
    }

    private function deleteWithReplies(Comment $comment): void
    {
        foreach ($this->comments->findByParent($comment->id()) as $reply) {
            $this->deleteWithReplies($reply);
        }

        $comment->markDeleted();

        $this->comments->delete($comment);
        $this->eventDispatcher->dispatch(...$comment->pullDomainEvents());
    }

    public function deleteAllForEntity(CommentableType $entityType, string $entityId): void
    {
        foreach ($this->comments->findByEntity($entityType, $entityId) as $comment) {
            $comment->markDeleted();

            $this->comments->delete($comment);
            $this->eventDispatcher->dispatch(...$comment->pullDomainEvents());
        }
    }
}
