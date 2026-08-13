<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\Event;

final class ProfileUpdated
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}
