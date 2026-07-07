<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Repository;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentId;

interface CommentRepositoryInterface
{
    public function save(Comment $comment): void;

    public function delete(Comment $comment): void;

    public function findById(CommentId $id): ?Comment;

    /**
     * Returns the replies to the given comment, oldest first.
     *
     * @return list<Comment>
     */
    public function findByParent(CommentId $parentId): array;

    /**
     * Returns the direct replies to all of the given comments, oldest first.
     *
     * @param list<string> $parentIds
     * @return list<Comment>
     */
    public function findByParents(array $parentIds): array;

    /**
     * Counts every comment of the given entity, replies included.
     */
    public function countAllByEntity(CommentableType $entityType, string $entityId): int;

    /**
     * Returns a page of root comments (replies excluded) for the given entity.
     *
     * @return list<Comment>
     */
    public function findByEntityPaginated(
        CommentableType $entityType,
        string $entityId,
        int $limit,
        int $offset,
    ): array;

    /**
     * Counts the root comments (replies excluded) for the given entity.
     */
    public function countByEntity(CommentableType $entityType, string $entityId): int;

    /** @return list<Comment> */
    public function findByEntity(CommentableType $entityType, string $entityId): array;
}
