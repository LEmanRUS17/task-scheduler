<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\ValueObject;

final class Username
{
    private const MAX_LENGTH = 50;

    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Username must not exceed %d characters', self::MAX_LENGTH)
            );
        }

        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
