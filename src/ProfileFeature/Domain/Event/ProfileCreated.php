<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\Event;

use App\ProfileFeature\Domain\ValueObject\ProfileId;

final class ProfileCreated
{
    public function __construct(
        public readonly ProfileId $profileId,
        public readonly string $userId,
    ) {}
}
