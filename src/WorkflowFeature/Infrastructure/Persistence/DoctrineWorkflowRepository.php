<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Persistence;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineWorkflowRepository implements WorkflowRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Workflow $workflow): void
    {
        $this->entityManager->persist($workflow);
        $this->entityManager->flush();
    }

    public function findById(WorkflowId $id): ?Workflow
    {
        return $this->entityManager->find(Workflow::class, $id->value());
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->entityManager->getRepository(Workflow::class)->findBy(['id' => $ids]);
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Workflow::class)->findAll();
    }

    public function findPaginated(int $limit, int $offset): array
    {
        return $this->entityManager->getRepository(Workflow::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
    }

    public function count(): int
    {
        return $this->entityManager->getRepository(Workflow::class)->count([]);
    }
}
