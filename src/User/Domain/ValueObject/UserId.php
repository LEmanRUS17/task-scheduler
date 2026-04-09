<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class UserId
{
    use StringValueObject;

    private function __construct(string $value) {}

    public static function generate(): self
    {
        return new self(sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        ));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
