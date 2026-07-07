<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Exception\CommentAccessDeniedException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\CommentFeature\Domain\Interactor\DeleteCommentInteractor;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;
use PHPUnit\Framework\TestCase;

final class DeleteCommentInteractorTest extends TestCase
{
    private CommentId $commentId;

    protected function setUp(): void
    {
        $this->commentId = CommentId::generate();
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

    private function buildInteractor(CommentRepositoryInterface $comments): DeleteCommentInteractor
    {
        return new DeleteCommentInteractor(
            $comments,
            $this->createStub(DomainEventDispatcherInterface::class),
        );
    }

    public function testDeleteRemovesComment(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($this->existingComment());
        $comments->method('findByParent')->willReturn([]);
        $comments->expects($this->once())->method('delete');

        $this->buildInteractor($comments)->delete($this->commentId, 'author-1');
    }

    public function testDeleteRemovesNestedRepliesTogetherWithComment(): void
    {
        $reply = Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-2',
            CommentContent::fromString('A reply'),
            new \DateTimeImmutable(),
            $this->commentId,
        );
        $reply->pullDomainEvents();

        $nestedReply = Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-3',
            CommentContent::fromString('A nested reply'),
            new \DateTimeImmutable(),
            $reply->id(),
        );
        $nestedReply->pullDomainEvents();

        $repliesByParent = [
            $this->commentId->value() => [$reply],
            $reply->id()->value() => [$nestedReply],
        ];

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($this->existingComment());
        $comments->method('findByParent')->willReturnCallback(
            static fn(CommentId $parentId) => $repliesByParent[$parentId->value()] ?? [],
        );
        $comments->expects($this->exactly(3))->method('delete');

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
        $comments->expects($this->never())->method('delete');

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
