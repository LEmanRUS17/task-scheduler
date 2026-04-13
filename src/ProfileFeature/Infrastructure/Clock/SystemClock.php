<?php

declare(strict_types=1);

namespace App\ProfileFeature\Infrastructure\Clock;

use App\ProfileFeature\Domain\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
