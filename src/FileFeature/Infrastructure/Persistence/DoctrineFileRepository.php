<?php

declare(strict_types=1);

namespace App\FileFeature\Infrastructure\Persistence;

use App\FileFeature\Domain\Entity\StoredFile;
use App\FileFeature\Domain\Repository\FileRepositoryInterface;
use App\FileFeature\Domain\ValueObject\FilePurpose;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineFileRepository implements FileRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(string $id): ?StoredFile
    {
        return $this->entityManager->find(StoredFile::class, $id);
    }

    public function findAvatar(string $entityClass, string $entityId): ?StoredFile
    {
        /** @var StoredFile|null */
        return $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(StoredFile::class, 'f')
            ->where('f.entityClass = :entityClass')
            ->andWhere('f.entityId = :entityId')
            ->andWhere('f.purpose = :purpose')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityId)
            ->setParameter('purpose', FilePurpose::Avatar->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAttachments(string $entityClass, string $entityId): array
    {
        /** @var list<StoredFile> $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(StoredFile::class, 'f')
            ->where('f.entityClass = :entityClass')
            ->andWhere('f.entityId = :entityId')
            ->andWhere('f.purpose = :purpose')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityId)
            ->setParameter('purpose', FilePurpose::Attachment->value)
            ->orderBy('f.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function save(StoredFile $file): void
    {
        $this->entityManager->persist($file);
        $this->entityManager->flush();
    }

    public function delete(StoredFile $file): void
    {
        $this->entityManager->remove($file);
        $this->entityManager->flush();
    }
}
