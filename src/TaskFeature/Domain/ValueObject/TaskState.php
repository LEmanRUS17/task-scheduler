<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\ValueObject;

enum TaskState: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case COMPLETED = 'completed';
    case OVERDUE = 'overdue';

    public static function resolve(
        bool $isClosed,
        bool $isCompleted,
        ?\DateTimeImmutable $scheduledStart,
        ?\DateTimeImmutable $scheduledEnd,
        \DateTimeImmutable $now,
    ): self {
        if ($isCompleted) {
            return self::COMPLETED;
        }

        if ($isClosed) {
            return self::CLOSED;
        }

        if ($scheduledEnd !== null && $now > $scheduledEnd) {
            return self::OVERDUE;
        }

        $started = $scheduledStart !== null && $now >= $scheduledStart;

        if ($started) {
            return self::ACTIVE;
        }

        return self::PLANNED;
    }
}
