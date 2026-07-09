<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Exception\CommentAccessDeniedException;
use App\CommentFeature\Domain\Exception\CommentDeletedException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Interactor\EditCommentInteractor;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;
use PHPUnit\Framework\TestCase;

final class EditCommentInteractorTest extends TestCase
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
            CommentContent::fromString('Original'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );
        $comment->pullDomainEvents();

        return $comment;
    }

    private function buildInteractor(CommentRepositoryInterface $comments): EditCommentInteractor
    {
        return new EditCommentInteractor(
            $comments,
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testEditUpdatesContentAndSaves(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($this->existingComment());
        $comments->expects($this->once())->method('save');

        $comment = $this->buildInteractor($comments)->edit(
            $this->commentId,
            'author-1',
            CommentContent::fromString('Edited'),
        );

        $this->assertSame('Edited', $comment->content()->value());
        $this->assertSame('2024-01-02 09:00:00', $comment->editedAt()?->format('Y-m-d H:i:s'));
    }

    public function testEditRejectsUnknownComment(): void
    {
        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn(null);

        $this->expectException(CommentNotFoundException::class);

        $this->buildInteractor($comments)->edit(
            $this->commentId,
            'author-1',
            CommentContent::fromString('Edited'),
        );
    }

    public function testEditRejectsDeletedComment(): void
    {
        $comment = $this->existingComment();
        $comment->markDeleted(new \DateTimeImmutable('2024-01-01 15:00:00'));
        $comment->pullDomainEvents();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($comment);
        $comments->expects($this->never())->method('save');

        $this->expectException(CommentDeletedException::class);

        $this->buildInteractor($comments)->edit(
            $this->commentId,
            'author-1',
            CommentContent::fromString('Edited'),
        );
    }

    public function testEditRejectsNonAuthor(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($this->existingComment());
        $comments->expects($this->never())->method('save');

        $this->expectException(CommentAccessDeniedException::class);

        $this->buildInteractor($comments)->edit(
            $this->commentId,
            'someone-else',
            CommentContent::fromString('Edited'),
        );
    }
}
