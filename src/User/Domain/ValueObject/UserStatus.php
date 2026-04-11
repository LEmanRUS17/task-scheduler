<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
