<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class HashedPassword
{
    use StringValueObject;

    private function __construct(string $value) {}

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }
}
