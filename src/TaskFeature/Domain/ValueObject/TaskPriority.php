<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\ValueObject;

enum TaskPriority: string
{
    case NO_PRIORITY = 'no_priority';
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}
