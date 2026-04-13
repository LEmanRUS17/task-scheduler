<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Event;

use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\UserId;

final class UserRegistered
{
    public function __construct(
        public readonly UserId $userId,
        public readonly Email $email,
    ) {}
}
