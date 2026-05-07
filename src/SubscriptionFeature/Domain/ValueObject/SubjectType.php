<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\ValueObject;

enum SubjectType: string
{
    case TASK = 'task';
}
