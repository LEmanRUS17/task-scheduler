<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Event;

use App\TagFeature\Domain\ValueObject\TagId;

final class TagDeleted
{
    public function __construct(
        public readonly TagId $id,
    ) {
    }
}
