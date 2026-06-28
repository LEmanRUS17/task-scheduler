<?php

declare(strict_types=1);

namespace App\TagFeature\Infrastructure\Clock;

use App\TagFeature\Domain\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
