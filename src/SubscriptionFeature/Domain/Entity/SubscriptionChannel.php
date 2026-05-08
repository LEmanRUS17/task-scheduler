<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Entity;

use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;

final class SubscriptionChannel
{
    private string $subscriptionId;
    private int $channel;

    private function __construct(string $subscriptionId, int $channel)
    {
        $this->subscriptionId = $subscriptionId;
        $this->channel = $channel;
    }

    public static function create(string $subscriptionId, NotificationChannel $channel): self
    {
        return new self($subscriptionId, $channel->value);
    }

    public function subscriptionId(): string
    {
        return $this->subscriptionId;
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::from($this->channel);
    }
}
