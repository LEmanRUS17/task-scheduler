<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\ValueObject;

final class TaskTitle
{
    use StringValueObject;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $title): self
    {
        $normalized = trim($title);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Title cannot be empty');
        }

        return new self($normalized);
    }
}
