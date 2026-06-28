<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\ValueObject;

final class TaggableType
{
    use StringValueObject;

    public const TASK = 'task';
    public const TEAM = 'team';
    public const WORKFLOW = 'workflow';

    private const ALLOWED = [self::TASK, self::TEAM, self::WORKFLOW];

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (!in_array($value, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(
                sprintf('Unknown taggable type "%s", expected one of: %s', $value, implode(', ', self::ALLOWED)),
            );
        }

        return new self($value);
    }
}
