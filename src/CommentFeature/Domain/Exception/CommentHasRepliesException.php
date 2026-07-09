<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Exception;

final class CommentHasRepliesException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Comment "%s" cannot be deleted because it has replies', $id));
    }
}
