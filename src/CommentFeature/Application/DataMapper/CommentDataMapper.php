<?php

declare(strict_types=1);

namespace App\CommentFeature\Application\DataMapper;

use App\CommentFeature\Application\DTOResponse\CommentResponseDTO;
use App\CommentFeature\Domain\Entity\Comment;

final class CommentDataMapper
{
    public function commentToResponse(Comment $comment): CommentResponseDTO
    {
        return new CommentResponseDTO(
            $comment->id()->value(),
            $comment->entityType()->value(),
            $comment->entityId(),
            $comment->authorId(),
            $comment->isDeleted() ? '' : $comment->content()->value(),
            $comment->createdAt(),
            $comment->editedAt(),
            $comment->parentId()?->value(),
            $comment->isDeleted(),
        );
    }
}
