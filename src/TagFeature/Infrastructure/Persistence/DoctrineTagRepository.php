<?php

declare(strict_types=1);

namespace App\TagFeature\Infrastructure\Persistence;

use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTagRepository implements TagRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Tag $tag): void
    {
        $this->entityManager->persist($tag);
        $this->entityManager->flush();
    }

    public function delete(Tag $tag): void
    {
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }

    public function findById(TagId $id): ?Tag
    {
        return $this->entityManager->find(Tag::class, $id->value());
    }

    public function findByOwnerAndName(string $ownerId, string $name): ?Tag
    {
        return $this->entityManager->getRepository(Tag::class)
            ->findOneBy(['ownerId' => $ownerId, 'name' => $name]);
    }

    public function findByOwnerPaginated(string $ownerId, int $limit, int $offset): array
    {
        return $this->entityManager->getRepository(Tag::class)
            ->findBy(['ownerId' => $ownerId], ['createdAt' => 'DESC'], $limit, $offset);
    }

    public function countByOwner(string $ownerId): int
    {
        return $this->entityManager->getRepository(Tag::class)->count(['ownerId' => $ownerId]);
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Tag::class)->findAll();
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->entityManager->getRepository(Tag::class)->findBy(['id' => $ids]);
    }
}
