<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Event;

use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;

final readonly class SubscriptionCreated
{
    public function __construct(
        public SubscriptionId $id,
        public string $userId,
        public SubjectType $subjectType,
        public string $subjectId,
    ) {}
}
