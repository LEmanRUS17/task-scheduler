<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Event;

use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;

final readonly class SubscriptionDeleted
{
    public function __construct(
        public SubscriptionId $id,
        public string $userId,
    ) {
    }
}
