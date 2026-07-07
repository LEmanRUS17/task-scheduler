<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Formatter;

use App\CommentFeatureApi\DTOResponse\CommentResponseInterface;

final class TaskCommentFormatter
{
    /** @return array<string, mixed> */
    public static function format(CommentResponseInterface $comment): array
    {
        return [
            'id' => $comment->getId(),
            'authorId' => $comment->getAuthorId(),
            'content' => $comment->getContent(),
            'parentId' => $comment->getParentId(),
            'createdAt' => $comment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'editedAt' => $comment->getEditedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param CommentResponseInterface[] $comments
     * @return list<array<string, mixed>>
     */
    public static function formatMany(array $comments): array
    {
        return array_values(array_map(
            static fn(CommentResponseInterface $comment) => self::format($comment),
            $comments,
        ));
    }
}
