<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
