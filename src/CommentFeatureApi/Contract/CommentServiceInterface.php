<?php

declare(strict_types=1);

namespace App\CommentFeatureApi\Contract;

use App\CommentFeatureApi\DTORequest\CreateCommentRequestInterface;
use App\CommentFeatureApi\DTORequest\UpdateCommentRequestInterface;
use App\CommentFeatureApi\DTOResponse\CommentResponseInterface;

interface CommentServiceInterface
{
    /**
     * Adds a comment to the given entity. The entity type is a free-form
     * lowercase slug chosen by the calling feature (e.g. "task", "team").
     *
     * When the request carries a parent id, the new comment is created as a
     * reply to that comment. The parent comment must belong to the same
     * entity, otherwise a \DomainException is thrown. Replies can be nested
     * to any depth and a comment can have any number of replies.
     */
    public function add(
        string $entityType,
        string $entityId,
        string $authorId,
        CreateCommentRequestInterface $request,
    ): CommentResponseInterface;

    /**
     * Returns the replies to the given comment, oldest first.
     *
     * @return CommentResponseInterface[]
     */
    public function getReplies(string $parentId): array;

    /**
     * Updates the comment content. Only the author may edit a comment.
     */
    public function update(
        string $id,
        string $authorId,
        UpdateCommentRequestInterface $request,
    ): CommentResponseInterface;

    /**
     * Deletes the comment. Only the author may delete a comment.
     */
    public function delete(string $id, string $authorId): void;

    /**
     * Returns the comment with the given id, or null when it does not exist.
     */
    public function getById(string $id): ?CommentResponseInterface;

    /**
     * Returns a page of root comments (replies excluded) for the given
     * entity, newest first.
     *
     * @return CommentResponseInterface[]
     */
    public function getEntityComments(string $entityType, string $entityId, int $limit, int $offset): array;

    /**
     * Counts the root comments (replies excluded) for the given entity.
     */
    public function countEntityComments(string $entityType, string $entityId): int;

    /**
     * Returns a page of the full comment thread for the given entity.
     *
     * Root comments are ordered by creation date (newest first) and paginated;
     * every reply is placed right after its parent (depth-first, oldest reply
     * first within a branch).
     *
     * @return CommentResponseInterface[]
     */
    public function getEntityCommentThread(string $entityType, string $entityId, int $limit, int $offset): array;

    /**
     * Counts every comment of the given entity, replies included.
     */
    public function countAllEntityComments(string $entityType, string $entityId): int;

    /**
     * Removes every comment attached to the given entity. Intended for the
     * owning feature to call when the commented entity itself is deleted.
     */
    public function deleteEntityComments(string $entityType, string $entityId): void;
}
