<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Infrastructure\Persistence;

use App\AuditLogFeature\Domain\Entity\AuditEntry;
use App\AuditLogFeature\Domain\Repository\AuditEntryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAuditEntryRepository implements AuditEntryRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(AuditEntry $entry): void
    {
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }
}
