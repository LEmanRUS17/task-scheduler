<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
