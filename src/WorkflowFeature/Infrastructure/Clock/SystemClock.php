<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Clock;

use App\WorkflowFeature\Domain\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
