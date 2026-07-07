<?php

declare(strict_types=1);

namespace App\CommentFeature\Infrastructure\Clock;

use App\CommentFeature\Domain\Port\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
