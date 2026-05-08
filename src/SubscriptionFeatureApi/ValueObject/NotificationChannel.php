<?php

declare(strict_types=1);

namespace App\SubscriptionFeatureApi\ValueObject;

enum NotificationChannel: int
{
    case EMAIL = 1;
    case IN_APP = 2;
}
