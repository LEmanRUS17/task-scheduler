<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\ValueObject;

trait StringValueObject
{
    private readonly string $value;

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
