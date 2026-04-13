<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\ValueObject;

final class HashedPassword
{
    use StringValueObject;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }
}
