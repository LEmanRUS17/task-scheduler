<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Exception;

final class CommentAccessDeniedException extends \DomainException
{
    public static function notAuthor(string $id): self
    {
        return new self(sprintf('Only the author may modify comment "%s"', $id));
    }
}
