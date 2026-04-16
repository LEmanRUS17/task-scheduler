<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Clock;

use App\TeamFeature\Domain\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
