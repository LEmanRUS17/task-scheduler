<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Persistence;

use App\TaskFeature\Domain\Entity\TaskStatusHistory;
use App\TaskFeature\Domain\Repository\TaskStatusHistoryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskStatusHistoryRepository implements TaskStatusHistoryRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(TaskStatusHistory $entry): void
    {
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    public function findByTaskId(string $taskId): array
    {
        return $this->entityManager->getRepository(TaskStatusHistory::class)->findBy(
            ['taskId' => $taskId],
            ['changedAt' => 'ASC'],
        );
    }
}
