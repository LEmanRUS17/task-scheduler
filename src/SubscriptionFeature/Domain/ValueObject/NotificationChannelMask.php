<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\ValueObject;

final class NotificationChannelMask
{
    public const int MAX = NotificationChannel::EMAIL->value | NotificationChannel::IN_APP->value;

    private function __construct(private readonly int $value) {}

    public static function fromInt(int $value): self
    {
        if ($value < 0 || $value > self::MAX) {
            throw new \InvalidArgumentException("Invalid notification channels mask: {$value}");
        }

        return new self($value);
    }

    public static function none(): self
    {
        return new self(0);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function has(NotificationChannel $channel): bool
    {
        return ($this->value & $channel->value) !== 0;
    }

    public function enable(NotificationChannel $channel): self
    {
        return new self($this->value | $channel->value);
    }

    public function disable(NotificationChannel $channel): self
    {
        return new self($this->value & ~$channel->value);
    }
}
