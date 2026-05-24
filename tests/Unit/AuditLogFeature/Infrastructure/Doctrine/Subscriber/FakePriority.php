<?php

declare(strict_types=1);

namespace App\Tests\Unit\AuditLogFeature\Infrastructure\Doctrine\Subscriber;

enum FakePriority: string
{
    case High = 'high';
    case Low  = 'low';
}
