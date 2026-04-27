<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Persistence;

use App\TaskFeature\Domain\Entity\TaskAssignee;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskAssigneeRepository implements TaskAssigneeRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(TaskAssignee $assignee): void
    {
        $this->entityManager->persist($assignee);
        $this->entityManager->flush();
    }

    public function findByTaskId(TaskId $taskId): array
    {
        return $this->entityManager->getRepository(TaskAssignee::class)->findBy([
            'taskId' => $taskId->value(),
        ]);
    }

    public function findByTaskAndUser(TaskId $taskId, string $userId): ?TaskAssignee
    {
        return $this->entityManager->getRepository(TaskAssignee::class)->findOneBy([
            'taskId' => $taskId->value(),
            'userId' => $userId,
        ]);
    }

    public function delete(TaskAssignee $assignee): void
    {
        $this->entityManager->remove($assignee);
        $this->entityManager->flush();
    }

    public function deleteByTaskId(TaskId $taskId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(TaskAssignee::class, 'a')
            ->where('a.taskId = :taskId')
            ->setParameter('taskId', $taskId->value())
            ->getQuery()
            ->execute();
    }
}
