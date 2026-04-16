<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\ValueObject;

enum TeamMemberRole: string
{
    case MEMBER = 'member';
    case OWNER = 'owner';
}
