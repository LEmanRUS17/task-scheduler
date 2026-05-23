<?php

declare(strict_types=1);

namespace App\Tests\Unit\SubscriptionFeature\Domain\ValueObject;

use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;
use PHPUnit\Framework\TestCase;

final class NotificationChannelMaskTest extends TestCase
{
    public function testFromIntStoresValue(): void
    {
        $mask = NotificationChannelMask::fromInt(1);

        $this->assertSame(1, $mask->value());
    }

    public function testNoneReturnsZero(): void
    {
        $this->assertSame(0, NotificationChannelMask::none()->value());
    }

    public function testHasReturnsTrueWhenChannelIsSet(): void
    {
        $mask = NotificationChannelMask::fromInt(NotificationChannel::EMAIL->value);

        $this->assertTrue($mask->has(NotificationChannel::EMAIL));
    }

    public function testHasReturnsFalseWhenChannelIsNotSet(): void
    {
        $mask = NotificationChannelMask::fromInt(NotificationChannel::EMAIL->value);

        $this->assertFalse($mask->has(NotificationChannel::IN_APP));
    }

    public function testNoneHasNoChannels(): void
    {
        $mask = NotificationChannelMask::none();

        $this->assertFalse($mask->has(NotificationChannel::EMAIL));
        $this->assertFalse($mask->has(NotificationChannel::IN_APP));
    }

    public function testEnableAddsChannel(): void
    {
        $mask = NotificationChannelMask::none()->enable(NotificationChannel::EMAIL);

        $this->assertTrue($mask->has(NotificationChannel::EMAIL));
        $this->assertFalse($mask->has(NotificationChannel::IN_APP));
    }

    public function testEnableDoesNotMutateOriginal(): void
    {
        $original = NotificationChannelMask::none();
        $original->enable(NotificationChannel::EMAIL);

        $this->assertFalse($original->has(NotificationChannel::EMAIL));
    }

    public function testDisableRemovesChannel(): void
    {
        $mask = NotificationChannelMask::fromInt(NotificationChannelMask::MAX)
            ->disable(NotificationChannel::EMAIL);

        $this->assertFalse($mask->has(NotificationChannel::EMAIL));
        $this->assertTrue($mask->has(NotificationChannel::IN_APP));
    }

    public function testFromIntAcceptsZero(): void
    {
        $mask = NotificationChannelMask::fromInt(0);

        $this->assertSame(0, $mask->value());
    }

    public function testFromIntAcceptsMax(): void
    {
        $mask = NotificationChannelMask::fromInt(NotificationChannelMask::MAX);

        $this->assertTrue($mask->has(NotificationChannel::EMAIL));
        $this->assertTrue($mask->has(NotificationChannel::IN_APP));
    }

    public function testFromIntThrowsOnNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        NotificationChannelMask::fromInt(-1);
    }

    public function testFromIntThrowsWhenExceedsMax(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        NotificationChannelMask::fromInt(NotificationChannelMask::MAX + 1);
    }
}
