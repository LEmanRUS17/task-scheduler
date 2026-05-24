<?php

declare(strict_types=1);

namespace App\Tests\Unit\AuditLogFeature\Infrastructure\Doctrine\Subscriber;

class StringableObject
{
    public function __construct(private readonly string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
