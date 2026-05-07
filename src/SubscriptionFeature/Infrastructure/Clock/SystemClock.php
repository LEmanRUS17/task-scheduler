<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Clock;

use App\SubscriptionFeature\Domain\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
