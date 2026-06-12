<?php

declare(strict_types=1);

namespace App\DescriptionFeature\Infrastructure\Persistence;

use App\DescriptionFeature\Domain\Entity\Description;
use App\DescriptionFeature\Domain\Repository\DescriptionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineDescriptionRepository implements DescriptionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findByEntity(string $entityClass, string $entityId): ?Description
    {
        /** @var Description|null */
        return $this->entityManager->createQueryBuilder()
            ->select('d')
            ->from(Description::class, 'd')
            ->where('d.entityClass = :entityClass')
            ->andWhere('d.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Description $description): void
    {
        $this->entityManager->persist($description);
        $this->entityManager->flush();
    }

    public function delete(Description $description): void
    {
        $this->entityManager->remove($description);
        $this->entityManager->flush();
    }
}
