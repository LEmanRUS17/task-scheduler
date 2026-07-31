<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\ValueObject;

enum TeamInvitationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
}
