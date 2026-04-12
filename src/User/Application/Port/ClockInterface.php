<?php

declare(strict_types=1);

namespace App\User\Application\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
