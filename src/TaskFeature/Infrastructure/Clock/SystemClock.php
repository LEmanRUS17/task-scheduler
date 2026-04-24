<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Clock;

use App\TaskFeature\Domain\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
