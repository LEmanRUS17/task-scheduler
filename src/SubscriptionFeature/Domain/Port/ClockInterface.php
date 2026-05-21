<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
