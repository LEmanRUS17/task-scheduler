<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
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
    ): Comment {
        $comment = Comment::create(
            CommentId::generate(),
            $entityType,
            $entityId,
            $authorId,
            $content,
            $this->clock->now(),
        );

        $this->comments->save($comment);
        $this->eventDispatcher->dispatch(...$comment->pullDomainEvents());

        return $comment;
    }
}
