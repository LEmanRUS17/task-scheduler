<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Exception;

final class CommentDeletedException extends \DomainException
{
    public static function alreadyDeleted(string $id): self
    {
        return new self(sprintf('Comment "%s" is already deleted', $id));
    }

    public static function cannotReplyTo(string $id): self
    {
        return new self(sprintf('Cannot reply to deleted comment "%s"', $id));
    }

    public static function cannotEdit(string $id): self
    {
        return new self(sprintf('Cannot edit deleted comment "%s"', $id));
    }
}
