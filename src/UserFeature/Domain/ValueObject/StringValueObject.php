<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\ValueObject;

trait StringValueObject
{
    private readonly string $value;

    /**
     * Get the value
     *
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Comparison of objects
     *
     * @param self $other
     *
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
