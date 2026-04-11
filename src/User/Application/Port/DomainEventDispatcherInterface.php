<?php

declare(strict_types=1);

namespace App\User\Application\Port;

interface DomainEventDispatcherInterface
{
    public function dispatch(object ...$events): void;
}
