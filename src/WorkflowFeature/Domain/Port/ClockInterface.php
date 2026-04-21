<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
