<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Exception;

final class CommentNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Comment "%s" not found', $id));
    }
}
