<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Event;

use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TaggableType;

final class TagAssigned
{
    public function __construct(
        public readonly TagId $tagId,
        public readonly TaggableType $entityType,
        public readonly string $entityId,
    ) {
    }
}
