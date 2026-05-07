<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Port;

interface DomainEventDispatcherInterface
{
    public function dispatch(object ...$events): void;
}
