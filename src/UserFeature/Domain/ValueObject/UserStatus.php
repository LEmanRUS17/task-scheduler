<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\ValueObject;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
