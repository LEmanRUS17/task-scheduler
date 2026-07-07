<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;

final class ReplyToCommentInteractor
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function reply(CommentId $parentId, string $authorId, CommentContent $content): Comment
    {
        $parent = $this->comments->findById($parentId);
        if ($parent === null) {
            throw CommentNotFoundException::withId($parentId->value());
        }

        $reply = Comment::create(
            CommentId::generate(),
            $parent->entityType(),
            $parent->entityId(),
            $authorId,
            $content,
            $this->clock->now(),
            $parent->id(),
        );

        $this->comments->save($reply);
        $this->eventDispatcher->dispatch(...$reply->pullDomainEvents());

        return $reply;
    }
}
