<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Port;

interface DomainEventDispatcherInterface
{
    public function dispatch(object ...$events): void;
}
