<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class Email
{
    use StringValueObject;

    private function __construct(string $value) {}

    public static function fromString(string $email): self
    {
        $normalized = strtolower(trim($email));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: {$email}");
        }

        return new self($normalized);
    }
}
