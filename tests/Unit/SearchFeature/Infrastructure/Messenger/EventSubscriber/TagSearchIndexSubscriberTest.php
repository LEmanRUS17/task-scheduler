<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\EventSubscriber\TagSearchIndexSubscriber;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTagMessage;
use App\TagFeature\Domain\Event\TagCreated;
use App\TagFeature\Domain\Event\TagDeleted;
use App\TagFeature\Domain\Event\TagUpdated;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TagSearchIndexSubscriberTest extends TestCase
{
    private const TAG_ID = '550e8400-e29b-4d4d-a716-446655440000';

    private function makeBus(?IndexTagMessage &$captured = null): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (IndexTagMessage $message) use (&$captured) {
                $captured = $message;
                return new Envelope($message);
            });

        return $bus;
    }

    public function testOnTagCreatedDispatchesIndexTagMessageWithTagId(): void
    {
        $event = new TagCreated(
            TagId::fromString(self::TAG_ID),
            'user-1',
            TagName::fromString('urgent'),
            TagColor::fromString('#ff0000'),
        );

        $captured = null;
        (new TagSearchIndexSubscriber($this->makeBus($captured)))->onTagCreated($event);

        $this->assertInstanceOf(IndexTagMessage::class, $captured);
        $this->assertSame(self::TAG_ID, $captured->tagId);
    }

    public function testOnTagUpdatedDispatchesIndexTagMessageWithTagId(): void
    {
        $event = new TagUpdated(
            TagId::fromString(self::TAG_ID),
            TagName::fromString('urgent'),
            TagColor::fromString('#ff0000'),
        );

        $captured = null;
        (new TagSearchIndexSubscriber($this->makeBus($captured)))->onTagUpdated($event);

        $this->assertInstanceOf(IndexTagMessage::class, $captured);
        $this->assertSame(self::TAG_ID, $captured->tagId);
    }

    public function testOnTagDeletedDispatchesIndexTagMessageWithTagId(): void
    {
        $event = new TagDeleted(TagId::fromString(self::TAG_ID));

        $captured = null;
        (new TagSearchIndexSubscriber($this->makeBus($captured)))->onTagDeleted($event);

        $this->assertInstanceOf(IndexTagMessage::class, $captured);
        $this->assertSame(self::TAG_ID, $captured->tagId);
    }
}
