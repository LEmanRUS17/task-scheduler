<?php

declare(strict_types=1);

namespace App\Tests\Unit\TagFeature\Domain\Interactor;

use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Entity\TagAssignment;
use App\TagFeature\Domain\Interactor\AssignTagInteractor;
use App\TagFeature\Domain\Port\ClockInterface;
use App\TagFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TagFeature\Domain\Repository\TagAssignmentRepositoryInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;
use App\TagFeature\Domain\ValueObject\TaggableType;
use PHPUnit\Framework\TestCase;

final class AssignTagInteractorTest extends TestCase
{
    private TagId $tagId;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->tagId = TagId::generate();
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));
    }

    private function existingTag(): Tag
    {
        return Tag::create(
            $this->tagId,
            'owner-1',
            TagName::fromString('urgent'),
            TagColor::fromString('#ff0000'),
            new \DateTimeImmutable(),
        );
    }

    private function buildInteractor(
        TagRepositoryInterface $tags,
        TagAssignmentRepositoryInterface $assignments,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): AssignTagInteractor {
        return new AssignTagInteractor(
            $tags,
            $assignments,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testAssignSavesNewAssignment(): void
    {
        $tags = $this->createStub(TagRepositoryInterface::class);
        $tags->method('findById')->willReturn($this->existingTag());

        $assignments = $this->createMock(TagAssignmentRepositoryInterface::class);
        $assignments->method('find')->willReturn(null);
        $assignments->expects($this->once())->method('save');

        $this->buildInteractor($tags, $assignments)->assign(
            $this->tagId,
            TaggableType::fromString('task'),
            'task-1',
            'user-1',
        );
    }

    public function testAssignIsIdempotentWhenAlreadyAssigned(): void
    {
        $tags = $this->createStub(TagRepositoryInterface::class);
        $tags->method('findById')->willReturn($this->existingTag());

        $existing = TagAssignment::create(
            'assignment-1',
            $this->tagId,
            TaggableType::fromString('task'),
            'task-1',
            'user-1',
            new \DateTimeImmutable(),
        );

        $assignments = $this->createMock(TagAssignmentRepositoryInterface::class);
        $assignments->method('find')->willReturn($existing);
        $assignments->expects($this->never())->method('save');

        $result = $this->buildInteractor($tags, $assignments)->assign(
            $this->tagId,
            TaggableType::fromString('task'),
            'task-1',
            'user-1',
        );

        $this->assertSame($existing, $result);
    }

    public function testAssignRejectsUnknownTag(): void
    {
        $tags = $this->createStub(TagRepositoryInterface::class);
        $tags->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tags, $this->createStub(TagAssignmentRepositoryInterface::class))->assign(
            $this->tagId,
            TaggableType::fromString('task'),
            'task-1',
            'user-1',
        );
    }
}
