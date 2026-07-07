<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Interactor\ReplyToCommentInteractor;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;
use PHPUnit\Framework\TestCase;

final class ReplyToCommentInteractorTest extends TestCase
{
    private CommentId $parentId;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->parentId = CommentId::generate();
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-02 09:00:00'));
    }

    private function rootComment(): Comment
    {
        $comment = Comment::create(
            $this->parentId,
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('Root'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );
        $comment->pullDomainEvents();

        return $comment;
    }

    private function buildInteractor(CommentRepositoryInterface $comments): ReplyToCommentInteractor
    {
        return new ReplyToCommentInteractor(
            $comments,
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testReplyInheritsEntityFromParent(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($this->rootComment());
        $comments->expects($this->once())->method('save');

        $reply = $this->buildInteractor($comments)->reply(
            $this->parentId,
            'author-2',
            CommentContent::fromString('I agree'),
        );

        $this->assertTrue($reply->isReply());
        $this->assertSame($this->parentId->value(), $reply->parentId()?->value());
        $this->assertSame('task', $reply->entityType()->value());
        $this->assertSame('task-1', $reply->entityId());
        $this->assertSame('author-2', $reply->authorId());
    }

    public function testReplyRejectsUnknownParent(): void
    {
        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn(null);

        $this->expectException(CommentNotFoundException::class);

        $this->buildInteractor($comments)->reply(
            $this->parentId,
            'author-2',
            CommentContent::fromString('I agree'),
        );
    }

    public function testReplyToReplyIsAllowed(): void
    {
        $existingReply = Comment::create(
            $this->parentId,
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('A reply'),
            new \DateTimeImmutable(),
            CommentId::generate(),
        );

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($existingReply);
        $comments->expects($this->once())->method('save');

        $nested = $this->buildInteractor($comments)->reply(
            $this->parentId,
            'author-2',
            CommentContent::fromString('Nested'),
        );

        $this->assertTrue($nested->isReply());
        $this->assertSame($this->parentId->value(), $nested->parentId()?->value());
        $this->assertSame('task-1', $nested->entityId());
    }
}
