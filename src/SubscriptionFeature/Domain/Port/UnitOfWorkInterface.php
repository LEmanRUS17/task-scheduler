<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Port;

interface UnitOfWorkInterface
{
    public function flush(): void;
}
