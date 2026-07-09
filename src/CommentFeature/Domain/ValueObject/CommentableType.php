<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\ValueObject;

/**
 * Identifies the kind of entity a comment is attached to.
 *
 * The type is a free-form lowercase slug chosen by the calling feature
 * (e.g. "task", "team", "workflow"). Only the format is validated here,
 * so new commentable entities can be introduced without changing this module.
 */
final class CommentableType
{
    use StringValueObject;

    private const MAX_LENGTH = 32;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $value) || strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid commentable type "%s": expected a lowercase slug up to %d characters',
                    $value,
                    self::MAX_LENGTH,
                ),
            );
        }

        return new self($value);
    }
}
