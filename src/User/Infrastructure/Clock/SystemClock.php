<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Clock;

use App\User\Application\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
