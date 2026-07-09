<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Application;

use App\CommentFeature\Application\ApiService\CommentApiService;
use App\CommentFeature\Application\DataMapper\CommentDataMapper;
use App\CommentFeature\Application\DTORequest\CreateCommentRequestDTO;
use App\CommentFeature\Application\DTORequestValidator\CommentValidatorInterface;
use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Interactor\AddCommentInteractor;
use App\CommentFeature\Domain\Interactor\DeleteCommentInteractor;
use App\CommentFeature\Domain\Interactor\EditCommentInteractor;
use App\CommentFeature\Domain\Port\ClockInterface;
use App\CommentFeature\Domain\Port\DomainEventDispatcherInterface;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;
use PHPUnit\Framework\TestCase;

final class CommentApiServiceTest extends TestCase
{
    private function buildService(
        CommentRepositoryInterface $comments,
        ?CommentValidatorInterface $validator = null,
    ): CommentApiService {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));

        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);

        if ($validator === null) {
            $validator = $this->createStub(CommentValidatorInterface::class);
            $validator->method('validate')->willReturn([]);
        }

        return new CommentApiService(
            new AddCommentInteractor($comments, $dispatcher, $clock),
            new EditCommentInteractor($comments, $dispatcher, $clock),
            new DeleteCommentInteractor($comments, $dispatcher, $clock),
            $comments,
            new CommentDataMapper(),
            $validator,
        );
    }

    public function testAddReturnsResponseForNewComment(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->expects($this->once())->method('save');

        $response = $this->buildService($comments)->add(
            'task',
            'task-1',
            'author-1',
            new CreateCommentRequestDTO('First!'),
        );

        $this->assertSame('task', $response->getEntityType());
        $this->assertSame('task-1', $response->getEntityId());
        $this->assertSame('author-1', $response->getAuthorId());
        $this->assertSame('First!', $response->getContent());
        $this->assertNull($response->getEditedAt());
    }

    public function testAddRejectsInvalidRequest(): void
    {
        $validator = $this->createStub(CommentValidatorInterface::class);
        $validator->method('validate')->willReturn(['content' => ['Content is required']]);

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->expects($this->never())->method('save');

        $this->expectException(\InvalidArgumentException::class);

        $this->buildService($comments, $validator)->add(
            'task',
            'task-1',
            'author-1',
            new CreateCommentRequestDTO(''),
        );
    }

    public function testAddRejectsUnknownEntityTypeFormat(): void
    {
        $comments = $this->createStub(CommentRepositoryInterface::class);

        $this->expectException(\InvalidArgumentException::class);

        $this->buildService($comments)->add(
            'Not A Slug!',
            'task-1',
            'author-1',
            new CreateCommentRequestDTO('First!'),
        );
    }

    public function testAddWithParentIdReturnsResponseWithParentId(): void
    {
        $parentId = CommentId::generate();
        $parent = Comment::create(
            $parentId,
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('Root'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($parent);
        $comments->expects($this->once())->method('save');

        $response = $this->buildService($comments)->add(
            'task',
            'task-1',
            'author-2',
            new CreateCommentRequestDTO('I agree', $parentId->value()),
        );

        $this->assertSame($parentId->value(), $response->getParentId());
        $this->assertSame('task', $response->getEntityType());
        $this->assertSame('task-1', $response->getEntityId());
    }

    public function testGetRepliesMapsEntities(): void
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

        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findByParent')->willReturn([$reply]);

        $result = $this->buildService($comments)->getReplies($parentId->value());

        $this->assertCount(1, $result);
        $this->assertSame($parentId->value(), $result[0]->getParentId());
    }

    public function testGetByIdReturnsNullWhenMissing(): void
    {
        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn(null);

        $this->assertNull($this->buildService($comments)->getById(CommentId::generate()->value()));
    }

    public function testGetEntityCommentsMapsEntities(): void
    {
        $comment = Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('First!'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );

        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findByEntityPaginated')->willReturn([$comment]);

        $result = $this->buildService($comments)->getEntityComments('task', 'task-1', 10, 0);

        $this->assertCount(1, $result);
        $this->assertSame($comment->id()->value(), $result[0]->getId());
    }

    public function testGetEntityCommentThreadPlacesRepliesRightAfterTheirParent(): void
    {
        $makeComment = static fn(string $createdAt, ?CommentId $parentId = null) => Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('Text'),
            new \DateTimeImmutable($createdAt),
            $parentId,
        );

        // Roots newest first, as the repository returns them.
        $newerRoot = $makeComment('2024-01-03 10:00:00');
        $olderRoot = $makeComment('2024-01-01 10:00:00');

        $firstReply = $makeComment('2024-01-02 10:00:00', $olderRoot->id());
        $secondReply = $makeComment('2024-01-04 10:00:00', $olderRoot->id());
        $nestedReply = $makeComment('2024-01-05 10:00:00', $firstReply->id());

        $repliesByParent = [
            $olderRoot->id()->value() => [$firstReply, $secondReply],
            $firstReply->id()->value() => [$nestedReply],
        ];

        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findByEntityPaginated')->willReturn([$newerRoot, $olderRoot]);
        $comments->method('findByParents')->willReturnCallback(
            static fn(array $parentIds) => array_merge(
                ...array_map(static fn(string $id) => $repliesByParent[$id] ?? [], $parentIds),
            ),
        );

        $result = $this->buildService($comments)->getEntityCommentThread('task', 'task-1', 10, 0);

        $this->assertSame(
            [
                $newerRoot->id()->value(),
                $olderRoot->id()->value(),
                $firstReply->id()->value(),
                $nestedReply->id()->value(),
                $secondReply->id()->value(),
            ],
            array_map(static fn($comment) => $comment->getId(), $result),
        );
    }

    public function testDeletedCommentIsMarkedAndItsContentIsHidden(): void
    {
        $comment = Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('Secret'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );
        $comment->pullDomainEvents();
        $comment->markDeleted(new \DateTimeImmutable('2024-01-02 09:00:00'));
        $comment->pullDomainEvents();

        $comments = $this->createStub(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($comment);

        $response = $this->buildService($comments)->getById($comment->id()->value());

        $this->assertNotNull($response);
        $this->assertTrue($response->isDeleted());
        $this->assertSame('', $response->getContent());
        $this->assertSame('author-1', $response->getAuthorId());
        $this->assertSame('2024-01-01 12:00:00', $response->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testDeleteMarksCommentDeletedWithoutRemovingIt(): void
    {
        $comment = Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('Bye'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );
        $comment->pullDomainEvents();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findById')->willReturn($comment);
        $comments->method('hasReplies')->willReturn(false);
        $comments->expects($this->once())->method('save');
        $comments->expects($this->never())->method('delete');

        $this->buildService($comments)->delete($comment->id()->value(), 'author-1');

        $this->assertTrue($comment->isDeleted());
    }

    public function testCountAllEntityCommentsDelegatesToRepository(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->expects($this->once())
            ->method('countAllByEntity')
            ->willReturn(7);

        $this->assertSame(7, $this->buildService($comments)->countAllEntityComments('task', 'task-1'));
    }

    public function testDeleteEntityCommentsDelegatesToInteractor(): void
    {
        $comment = Comment::create(
            CommentId::generate(),
            CommentableType::fromString('task'),
            'task-1',
            'author-1',
            CommentContent::fromString('First!'),
            new \DateTimeImmutable(),
        );
        $comment->pullDomainEvents();

        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->method('findByEntity')->willReturn([$comment]);
        $comments->expects($this->once())->method('delete');

        $this->buildService($comments)->deleteEntityComments('task', 'task-1');
    }
}
