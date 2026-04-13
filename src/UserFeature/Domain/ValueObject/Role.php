<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\ValueObject;

enum Role: string
{
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';

    case Leader = 'ROLE_LEADER';
    case Member = 'ROLE_MEMBER';
}
