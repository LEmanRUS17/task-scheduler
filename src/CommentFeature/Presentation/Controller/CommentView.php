<?php

declare(strict_types=1);

namespace App\CommentFeature\Presentation\Controller;

use App\CommentFeatureApi\DTOResponse\CommentResponseInterface;

final class CommentView
{
    /** @return array<string, mixed> */
    public static function one(CommentResponseInterface $comment): array
    {
        return [
            'id' => $comment->getId(),
            'entityType' => $comment->getEntityType(),
            'entityId' => $comment->getEntityId(),
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
    public static function many(array $comments): array
    {
        return array_values(array_map(
            static fn(CommentResponseInterface $comment) => self::one($comment),
            $comments,
        ));
    }
}
