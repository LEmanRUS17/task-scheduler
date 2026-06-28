<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\ValueObject;

final class TagColor
{
    use StringValueObject;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (!preg_match('/^#[0-9a-f]{6}$/', $value)) {
            throw new \InvalidArgumentException("Invalid color, expected #RRGGBB: \"{$value}\"");
        }

        return new self($value);
    }
}
