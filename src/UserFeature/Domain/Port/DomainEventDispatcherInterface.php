<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Port;

interface DomainEventDispatcherInterface
{
    public function dispatch(object ...$events): void;
}
