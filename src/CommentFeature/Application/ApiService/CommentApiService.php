<?php

declare(strict_types=1);

namespace App\CommentFeature\Application\ApiService;

use App\CommentFeature\Application\DataMapper\CommentDataMapper;
use App\CommentFeature\Application\DTORequestValidator\CommentValidatorInterface;
use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Interactor\AddCommentInteractor;
use App\CommentFeature\Domain\Interactor\DeleteCommentInteractor;
use App\CommentFeature\Domain\Interactor\EditCommentInteractor;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;
use App\CommentFeatureApi\Contract\CommentServiceInterface;
use App\CommentFeatureApi\DTORequest\CreateCommentRequestInterface;
use App\CommentFeatureApi\DTORequest\UpdateCommentRequestInterface;
use App\CommentFeatureApi\DTOResponse\CommentResponseInterface;

final class CommentApiService implements CommentServiceInterface
{
    public function __construct(
        private readonly AddCommentInteractor $addInteractor,
        private readonly EditCommentInteractor $editInteractor,
        private readonly DeleteCommentInteractor $deleteInteractor,
        private readonly CommentRepositoryInterface $comments,
        private readonly CommentDataMapper $dataMapper,
        private readonly CommentValidatorInterface $validator,
    ) {
    }

    public function add(
        string $entityType,
        string $entityId,
        string $authorId,
        CreateCommentRequestInterface $request,
    ): CommentResponseInterface {
        $this->guardValid($request);

        $parentId = $request->getParentId();

        $comment = $this->addInteractor->add(
            CommentableType::fromString($entityType),
            $entityId,
            $authorId,
            CommentContent::fromString($request->getContent()),
            $parentId !== null ? CommentId::fromString($parentId) : null,
        );

        return $this->dataMapper->commentToResponse($comment);
    }

    /** @return CommentResponseInterface[] */
    public function getReplies(string $parentId): array
    {
        return array_map(
            fn(Comment $comment) => $this->dataMapper->commentToResponse($comment),
            $this->comments->findByParent(CommentId::fromString($parentId)),
        );
    }

    public function update(
        string $id,
        string $authorId,
        UpdateCommentRequestInterface $request,
    ): CommentResponseInterface {
        $this->guardValid($request);

        $comment = $this->editInteractor->edit(
            CommentId::fromString($id),
            $authorId,
            CommentContent::fromString($request->getContent()),
        );

        return $this->dataMapper->commentToResponse($comment);
    }

    public function delete(string $id, string $authorId): void
    {
        $this->deleteInteractor->delete(CommentId::fromString($id), $authorId);
    }

    public function getById(string $id): ?CommentResponseInterface
    {
        $comment = $this->comments->findById(CommentId::fromString($id));

        return $comment !== null ? $this->dataMapper->commentToResponse($comment) : null;
    }

    /** @return CommentResponseInterface[] */
    public function getEntityComments(string $entityType, string $entityId, int $limit, int $offset): array
    {
        return array_map(
            fn(Comment $comment) => $this->dataMapper->commentToResponse($comment),
            $this->comments->findByEntityPaginated(
                CommentableType::fromString($entityType),
                $entityId,
                $limit,
                $offset,
            ),
        );
    }

    public function countEntityComments(string $entityType, string $entityId): int
    {
        return $this->comments->countByEntity(CommentableType::fromString($entityType), $entityId);
    }

    /** @return CommentResponseInterface[] */
    public function getEntityCommentThread(string $entityType, string $entityId, int $limit, int $offset): array
    {
        $roots = $this->comments->findByEntityPaginated(
            CommentableType::fromString($entityType),
            $entityId,
            $limit,
            $offset,
        );

        $childrenByParent = $this->loadDescendants(array_map(
            static fn(Comment $comment) => $comment->id()->value(),
            $roots,
        ));

        $thread = [];
        foreach ($roots as $root) {
            $this->flattenBranch($root, $childrenByParent, $thread);
        }

        return array_map(
            fn(Comment $comment) => $this->dataMapper->commentToResponse($comment),
            $thread,
        );
    }

    public function countAllEntityComments(string $entityType, string $entityId): int
    {
        return $this->comments->countAllByEntity(CommentableType::fromString($entityType), $entityId);
    }

    /**
     * Loads all descendants of the given comments level by level and groups
     * them by parent id, keeping the oldest-first order within each parent.
     *
     * @param list<string> $parentIds
     * @return array<string, list<Comment>>
     */
    private function loadDescendants(array $parentIds): array
    {
        $childrenByParent = [];

        while ($parentIds !== []) {
            $level = $this->comments->findByParents($parentIds);

            $parentIds = [];
            foreach ($level as $reply) {
                $childrenByParent[$reply->parentId()?->value() ?? ''][] = $reply;
                $parentIds[] = $reply->id()->value();
            }
        }

        return $childrenByParent;
    }

    /**
     * @param array<string, list<Comment>> $childrenByParent
     * @param list<Comment> $thread
     */
    private function flattenBranch(Comment $comment, array $childrenByParent, array &$thread): void
    {
        $thread[] = $comment;

        foreach ($childrenByParent[$comment->id()->value()] ?? [] as $reply) {
            $this->flattenBranch($reply, $childrenByParent, $thread);
        }
    }

    public function deleteEntityComments(string $entityType, string $entityId): void
    {
        $this->deleteInteractor->deleteAllForEntity(CommentableType::fromString($entityType), $entityId);
    }

    private function guardValid(object $request): void
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }
    }
}
