<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Event;

use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\Title;

final class TeamCreated
{
    public function __construct(
        public readonly TeamId $id,
        public readonly Title $title,
    ) {}
}
