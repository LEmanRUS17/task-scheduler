<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Persistence;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Task $task): void
    {
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Task::class)->findAll();
    }

    public function findById(TaskId $id): ?Task
    {
        return $this->entityManager->find(Task::class, $id->value());
    }

    public function delete(TaskId $id): void
    {
        $task = $this->findById($id);
        if ($task === null) {
            return;
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }
}
