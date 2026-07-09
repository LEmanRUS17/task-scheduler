<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\Entity;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Event\CommentAdded;
use App\CommentFeature\Domain\Event\CommentDeleted;
use App\CommentFeature\Domain\Event\CommentUpdated;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;
use PHPUnit\Framework\TestCase;

final class CommentTest extends TestCase
{
    private function makeComment(): Comment
    {
        return Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('First!'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );
    }

    public function testCreateRecordsCommentAddedEvent(): void
    {
        $events = $this->makeComment()->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(CommentAdded::class, $events[0]);
    }

    public function testPullDomainEventsClearsBuffer(): void
    {
        $comment = $this->makeComment();
        $comment->pullDomainEvents();

        $this->assertSame([], $comment->pullDomainEvents());
    }

    public function testEditChangesContentAndRecordsEvent(): void
    {
        $comment = $this->makeComment();
        $comment->pullDomainEvents();

        $editedAt = new \DateTimeImmutable('2024-01-02 09:00:00');
        $comment->edit(CommentContent::fromString('Edited'), $editedAt);

        $this->assertSame('Edited', $comment->content()->value());
        $this->assertSame($editedAt, $comment->editedAt());

        $events = $comment->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CommentUpdated::class, $events[0]);
    }

    public function testMarkDeletedSetsDeletedAtAndRecordsEvent(): void
    {
        $comment = $this->makeComment();
        $comment->pullDomainEvents();

        $deletedAt = new \DateTimeImmutable('2024-01-03 08:00:00');
        $comment->markDeleted($deletedAt);

        $this->assertTrue($comment->isDeleted());
        $this->assertSame($deletedAt, $comment->deletedAt());

        $events = $comment->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CommentDeleted::class, $events[0]);
    }

    public function testFreshCommentIsNotDeleted(): void
    {
        $comment = $this->makeComment();

        $this->assertFalse($comment->isDeleted());
        $this->assertNull($comment->deletedAt());
    }

    public function testIsAuthoredBy(): void
    {
        $comment = $this->makeComment();

        $this->assertTrue($comment->isAuthoredBy('author-1'));
        $this->assertFalse($comment->isAuthoredBy('someone-else'));
    }

    public function testRootCommentIsNotAReply(): void
    {
        $comment = $this->makeComment();

        $this->assertFalse($comment->isReply());
        $this->assertNull($comment->parentId());
    }

    public function testReplyExposesParentId(): void
    {
        $parentId = CommentId::generate();

        $reply = Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-2',
            CommentContent::fromString('I agree'),
            new \DateTimeImmutable('2024-01-02 09:00:00'),
            $parentId,
        );

        $this->assertTrue($reply->isReply());
        $this->assertSame($parentId->value(), $reply->parentId()?->value());
    }

    public function testExposesEntityReferenceAndTimestamps(): void
    {
        $comment = $this->makeComment();

        $this->assertSame('task', $comment->entityType()->value());
        $this->assertSame('task-1', $comment->entityId());
        $this->assertSame('author-1', $comment->authorId());
        $this->assertSame('2024-01-01 12:00:00', $comment->createdAt()->format('Y-m-d H:i:s'));
        $this->assertNull($comment->editedAt());
    }
}
