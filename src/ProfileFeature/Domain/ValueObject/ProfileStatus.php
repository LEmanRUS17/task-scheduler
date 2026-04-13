<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\ValueObject;

final class ProfileStatus
{
    private const MAX_LENGTH = 160;

    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Status must not exceed %d characters', self::MAX_LENGTH)
            );
        }

        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }
}
