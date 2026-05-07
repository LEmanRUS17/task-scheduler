<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Exception;

final class SubscriptionNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Subscription {$id} not found");
    }
}
