<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Event;

use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentId;

final class CommentAdded
{
    public function __construct(
        public readonly CommentId $commentId,
        public readonly CommentableType $entityType,
        public readonly string $entityId,
        public readonly string $authorId,
        public readonly ?CommentId $parentId = null,
    ) {
    }
}
