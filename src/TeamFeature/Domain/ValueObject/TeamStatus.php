<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\ValueObject;

enum TeamStatus: string
{
    case ACTIVE = 'active';
    case NOT_ACTIVE = 'not_active';
}
