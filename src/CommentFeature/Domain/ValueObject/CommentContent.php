<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\ValueObject;

final class CommentContent
{
    use StringValueObject;

    private const MAX_LENGTH = 10000;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Comment content must not be empty');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Comment content must not exceed %d characters', self::MAX_LENGTH),
            );
        }

        return new self($value);
    }
}
