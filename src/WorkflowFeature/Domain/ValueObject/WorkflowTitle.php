<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\ValueObject;

final class WorkflowTitle
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
            throw new \InvalidArgumentException('Workflow title must not be empty');
        }

        if (strlen($value) > 255) {
            throw new \InvalidArgumentException('Workflow title must not exceed 255 characters');
        }

        return new self($value);
    }
}
