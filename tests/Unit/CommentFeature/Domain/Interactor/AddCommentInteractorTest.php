<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\Interactor;

use App\CommentFeature\Domain\Event\CommentAdded;
use App\CommentFeature\Domain\Interactor\AddCommentInteractor;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
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
    }
}
