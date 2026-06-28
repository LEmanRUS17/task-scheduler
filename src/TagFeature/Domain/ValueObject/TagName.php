<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\ValueObject;

final class TagName
{
    use StringValueObject;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Tag name must not be empty');
        }

        if (mb_strlen($value) > 64) {
            throw new \InvalidArgumentException('Tag name must not exceed 64 characters');
        }

        return new self($value);
    }
}
