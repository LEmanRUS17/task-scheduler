<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Event\CommentAdded;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Interactor\AddCommentInteractor;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;
use PHPUnit\Framework\TestCase;

final class AddCommentInteractorTest extends TestCase
{
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));
    }

    public function testAddSavesCommentAndDispatchesEvent(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->expects($this->once())->method('save');

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CommentAdded::class));

        $interactor = new AddCommentInteractor($comments, $dispatcher, $this->clock);

        $comment = $interactor->add(
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('First!'),
        );

        $this->assertSame('task', $comment->entityType()->value());
        $this->assertSame('task-1', $comment->entityId());
        $this->assertSame('author-1', $comment->authorId());
        $this->assertSame('First!', $comment->content()->value());
        $this->assertSame('2024-01-01 12:00:00', $comment->createdAt()->format('Y-m-d H:i:s'));
        $this->assertFalse($comment->isReply());
    }

    private function buildInteractor(CommentRepositoryInterface $comments): AddCommentInteractor
    {
        return new AddCommentInteractor(
            $comments,
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    private function existingComment(CommentId $id, ?CommentId $parentId = null): Comment
    {
        $comment = Comment::create(
            $id,
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('Root'),
            new \DateTimeImmutable('2024-01-01 10:00:00'),
            $parentId,
        );
        $comment->pullDomainEvents();

        return $comment;
    }

    public function testAddWithParentCreatesReply(): void
    {
        $parentId = CommentId::generate();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($this->existingComment($parentId));
        $comments->expects($this->once())->method('save');

        $reply = $this->buildInteractor($comments)->add(
            CommentableType::fromString('task'),
            'task-1',
            'author-2',
            CommentContent::fromString('I agree'),
            $parentId,
        );

        $this->assertTrue($reply->isReply());
        $this->assertSame($parentId->value(), $reply->parentId()?->value());
        $this->assertSame('task-1', $reply->entityId());
        $this->assertSame('author-2', $reply->authorId());
    }

    public function testAddRejectsUnknownParent(): void
    {
        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn(null);

        $this->expectException(CommentNotFoundException::class);

        $this->buildInteractor($comments)->add(
            CommentableType::fromString('task'),
            'task-1',
            'author-2',
            CommentContent::fromString('I agree'),
            CommentId::generate(),
        );
    }

    public function testAddRejectsParentFromAnotherEntity(): void
    {
        $parentId = CommentId::generate();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($this->existingComment($parentId));
        $comments->expects($this->never())->method('save');

        $this->expectException(CommentNotFoundException::class);

        $this->buildInteractor($comments)->add(
            CommentableType::fromString('task'),
            'task-2',
            'author-2',
            CommentContent::fromString('I agree'),
            $parentId,
        );
    }

    public function testReplyToReplyIsAllowed(): void
    {
        $parentId = CommentId::generate();
        $existingReply = $this->existingComment($parentId, CommentId::generate());

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($existingReply);
        $comments->expects($this->once())->method('save');

        $nested = $this->buildInteractor($comments)->add(
            CommentableType::fromString('task'),
            'task-1',
            'author-2',
            CommentContent::fromString('Nested'),
            $parentId,
        );

        $this->assertTrue($nested->isReply());
        $this->assertSame($parentId->value(), $nested->parentId()?->value());
    }
}
