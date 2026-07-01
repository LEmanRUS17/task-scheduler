<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Event;

use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;

final class TagCreated
{
    public function __construct(
        public readonly TagId $id,
        public readonly string $ownerId,
        public readonly TagName $name,
        public readonly TagColor $color,
    ) {
    }
}
