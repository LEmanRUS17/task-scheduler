<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
