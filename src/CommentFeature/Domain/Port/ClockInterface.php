<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
