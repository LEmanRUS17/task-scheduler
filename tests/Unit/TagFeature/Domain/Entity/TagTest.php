<?php

declare(strict_types=1);

namespace App\Tests\Unit\TagFeature\Domain\Entity;

use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Event\TagCreated;
use App\TagFeature\Domain\Event\TagUpdated;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;
use PHPUnit\Framework\TestCase;

final class TagTest extends TestCase
{
    private function makeTag(): Tag
    {
        return Tag::create(
            TagId::generate(),
            'owner-1',
            TagName::fromString('urgent'),
            TagColor::fromString('#ff0000'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );
    }

    public function testCreateRecordsTagCreatedEvent(): void
    {
        $events = $this->makeTag()->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TagCreated::class, $events[0]);
    }

    public function testPullDomainEventsClearsBuffer(): void
    {
        $tag = $this->makeTag();
        $tag->pullDomainEvents();

        $this->assertSame([], $tag->pullDomainEvents());
    }

    public function testUpdateChangesFieldsAndRecordsEvent(): void
    {
        $tag = $this->makeTag();
        $tag->pullDomainEvents();

        $tag->update(TagName::fromString('blocker'), TagColor::fromString('#00ff00'));

        $this->assertSame('blocker', $tag->name()->value());
        $this->assertSame('#00ff00', $tag->color()->value());

        $events = $tag->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TagUpdated::class, $events[0]);
    }

    public function testExposesOwnerAndCreatedAt(): void
    {
        $tag = $this->makeTag();

        $this->assertSame('owner-1', $tag->ownerId());
        $this->assertSame('2024-01-01 12:00:00', $tag->createdAt()->format('Y-m-d H:i:s'));
    }
}
