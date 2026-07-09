<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Event\CommentDeleted;
use App\CommentFeature\Domain\Exception\CommentAccessDeniedException;
use App\CommentFeature\Domain\Exception\CommentDeletedException;
use App\CommentFeature\Domain\Exception\CommentHasRepliesException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Interactor\DeleteCommentInteractor;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;
use PHPUnit\Framework\TestCase;

final class DeleteCommentInteractorTest extends TestCase
{
    private CommentId $commentId;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->commentId = CommentId::generate();
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-02 09:00:00'));
    }

    private function existingComment(): Comment
    {
        $comment = Comment::create(
            $this->commentId,
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('Bye'),
            new \DateTimeImmutable(),
        );
        $comment->pullDomainEvents();

        return $comment;
    }

    private function buildInteractor(
        CommentRepositoryInterface $comments,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): DeleteCommentInteractor {
        return new DeleteCommentInteractor(
            $comments,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testDeleteMarksCommentDeletedAndKeepsIt(): void
    {
        $comment = $this->existingComment();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($comment);
        $comments->method('hasReplies')->willReturn(false);
        $comments->expects($this->once())->method('save')->with($comment);
        $comments->expects($this->never())->method('delete');

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CommentDeleted::class));

        $this->buildInteractor($comments, $dispatcher)->delete($this->commentId, 'author-1');

        $this->assertTrue($comment->isDeleted());
        $this->assertSame('2024-01-02 09:00:00', $comment->deletedAt()?->format('Y-m-d H:i:s'));
    }

    public function testDeleteRejectsCommentWithReplies(): void
    {
        $comment = $this->existingComment();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($comment);
        $comments->method('hasReplies')->willReturn(true);
        $comments->expects($this->never())->method('save');

        $this->expectException(CommentHasRepliesException::class);

        $this->buildInteractor($comments)->delete($this->commentId, 'author-1');
    }

    public function testDeleteRejectsAlreadyDeletedComment(): void
    {
        $comment = $this->existingComment();
        $comment->markDeleted(new \DateTimeImmutable('2024-01-01 15:00:00'));
        $comment->pullDomainEvents();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($comment);
        $comments->expects($this->never())->method('save');

        $this->expectException(CommentDeletedException::class);

        $this->buildInteractor($comments)->delete($this->commentId, 'author-1');
    }

    public function testDeleteRejectsUnknownComment(): void
    {
        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn(null);

        $this->expectException(CommentNotFoundException::class);

        $this->buildInteractor($comments)->delete($this->commentId, 'author-1');
    }

    public function testDeleteRejectsNonAuthor(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($this->existingComment());
        $comments->expects($this->never())->method('save');

        $this->expectException(CommentAccessDeniedException::class);

        $this->buildInteractor($comments)->delete($this->commentId, 'someone-else');
    }

    public function testDeleteAllForEntityRemovesEveryComment(): void
    {
        $first = $this->existingComment();
        $second = Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-2',
            CommentContent::fromString('Me too'),
            new \DateTimeImmutable(),
        );
        $second->pullDomainEvents();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findByEntity')->willReturn([$first, $second]);
        $comments->expects($this->exactly(2))->method('delete');

        $this->buildInteractor($comments)->deleteAllForEntity(
            CommentableType::fromString('task'),
            'task-1',
        );
    }
}
