<?php

declare(strict_types=1);

namespace App\CommentFeature\Infrastructure\Persistence;

use App\CommentFeature\Domain\Entity\Comment;
use App\CommentFeature\Domain\Repository\CommentRepositoryInterface;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Comment $comment): void
    {
        $this->entityManager->persist($comment);
        $this->entityManager->flush();
    }

    public function delete(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }

    public function findById(CommentId $id): ?Comment
    {
        return $this->entityManager->getRepository(Comment::class)->find($id->value());
    }

    public function findByParent(CommentId $parentId): array
    {
        /** @var list<Comment> */
        return $this->entityManager->getRepository(Comment::class)->findBy(
            ['parentId' => $parentId->value()],
            ['createdAt' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function hasReplies(CommentId $parentId): bool
    {
        return $this->entityManager->getRepository(Comment::class)->count([
            'parentId' => $parentId->value(),
        ]) > 0;
    }

    public function findByEntityPaginated(
        CommentableType $entityType,
        string $entityId,
        int $limit,
        int $offset,
    ): array {
        /** @var list<Comment> */
        return $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->where('c.entityType = :type')
            ->andWhere('c.entityId = :id')
            ->andWhere('c.parentId IS NULL')
            ->setParameter('type', $entityType->value())
            ->setParameter('id', $entityId)
            ->orderBy('c.createdAt', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByParents(array $parentIds): array
    {
        if ($parentIds === []) {
            return [];
        }

        /** @var list<Comment> */
        return $this->entityManager->getRepository(Comment::class)->findBy(
            ['parentId' => $parentIds],
            ['createdAt' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function countAllByEntity(CommentableType $entityType, string $entityId): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Comment::class, 'c')
            ->where('c.entityType = :type')
            ->andWhere('c.entityId = :id')
            ->setParameter('type', $entityType->value())
            ->setParameter('id', $entityId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByEntity(CommentableType $entityType, string $entityId): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Comment::class, 'c')
            ->where('c.entityType = :type')
            ->andWhere('c.entityId = :id')
            ->andWhere('c.parentId IS NULL')
            ->setParameter('type', $entityType->value())
            ->setParameter('id', $entityId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByEntity(CommentableType $entityType, string $entityId): array
    {
        /** @var list<Comment> */
        return $this->entityManager->getRepository(Comment::class)->findBy([
            'entityType' => $entityType->value(),
            'entityId' => $entityId,
        ]);
    }
}
