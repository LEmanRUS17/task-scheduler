<?php

declare(strict_types=1);

namespace App\Tests\Unit\SubscriptionFeature\Domain\Entity;

use App\SubscriptionFeature\Domain\Entity\SubscriptionChannel;
use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;
use PHPUnit\Framework\TestCase;

final class SubscriptionChannelTest extends TestCase
{
    public function testCreateStoresFields(): void
    {
        $channel = SubscriptionChannel::create('sub-uuid', NotificationChannel::EMAIL);

        $this->assertSame('sub-uuid', $channel->subscriptionId());
        $this->assertSame(NotificationChannel::EMAIL, $channel->channel());
    }

    public function testCreateStoresInAppChannel(): void
    {
        $channel = SubscriptionChannel::create('sub-uuid', NotificationChannel::IN_APP);

        $this->assertSame(NotificationChannel::IN_APP, $channel->channel());
    }
}
