<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\ValueObject;

final class TransitionName
{
    use StringValueObject;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Transition name must not be empty');
        }

        if (preg_match('/\s/', $value)) {
            throw new \InvalidArgumentException('Transition name must not contain whitespace');
        }

        if (strlen($value) > 100) {
            throw new \InvalidArgumentException('Transition name must not exceed 100 characters');
        }

        return new self($value);
    }
}
