<?php

declare(strict_types=1);

namespace App\Tests\Unit\TagFeature\Domain\Interactor;

use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Interactor\CreateTagInteractor;
use App\TagFeature\Domain\Port\ClockInterface;
use App\TagFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagName;
use PHPUnit\Framework\TestCase;

final class CreateTagInteractorTest extends TestCase
{
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));
    }

    private function buildInteractor(
        TagRepositoryInterface $tags,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): CreateTagInteractor {
        return new CreateTagInteractor(
            $tags,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testCreateSavesAndReturnsTag(): void
    {
        $tags = $this->createMock(TagRepositoryInterface::class);
        $tags->method('findByOwnerAndName')->willReturn(null);
        $tags->expects($this->once())->method('save');

        $tag = $this->buildInteractor($tags)->create(
            'owner-1',
            TagName::fromString('urgent'),
            TagColor::fromString('#ff0000'),
        );

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertSame('urgent', $tag->name()->value());
        $this->assertSame('owner-1', $tag->ownerId());
    }

    public function testCreateDispatchesEvent(): void
    {
        $tags = $this->createStub(TagRepositoryInterface::class);
        $tags->method('findByOwnerAndName')->willReturn(null);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor($tags, $dispatcher)->create(
            'owner-1',
            TagName::fromString('urgent'),
            TagColor::fromString('#ff0000'),
        );
    }

    public function testCreateRejectsDuplicateName(): void
    {
        $existing = Tag::create(
            \App\TagFeature\Domain\ValueObject\TagId::generate(),
            'owner-1',
            TagName::fromString('urgent'),
            TagColor::fromString('#ff0000'),
            new \DateTimeImmutable(),
        );

        $tags = $this->createStub(TagRepositoryInterface::class);
        $tags->method('findByOwnerAndName')->willReturn($existing);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tags)->create(
            'owner-1',
            TagName::fromString('urgent'),
            TagColor::fromString('#00ff00'),
        );
    }
}
