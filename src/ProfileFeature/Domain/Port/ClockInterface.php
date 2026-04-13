<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
