<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Clock;

use App\UserFeature\Domain\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
