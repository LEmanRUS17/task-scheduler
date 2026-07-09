<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Exception\CommentAccessDeniedException;
use App\CommentFeature\Domain\Exception\CommentDeletedException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;

final class EditCommentInteractor
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function edit(CommentId $id, string $authorId, CommentContent $content): Comment
    {
        $comment = $this->comments->findById($id);
        if ($comment === null) {
            throw CommentNotFoundException::withId($id->value());
        }

        if (!$comment->isAuthoredBy($authorId)) {
            throw CommentAccessDeniedException::notAuthor($id->value());
        }

        if ($comment->isDeleted()) {
            throw CommentDeletedException::cannotEdit($id->value());
        }

        $comment->edit($content, $this->clock->now());

        $this->comments->save($comment);
        $this->eventDispatcher->dispatch(...$comment->pullDomainEvents());

        return $comment;
    }
}
