<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Persistence;

use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineWorkflowTransitionRepository implements WorkflowTransitionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(WorkflowTransition $transition): void
    {
        $this->entityManager->persist($transition);
        $this->entityManager->flush();
    }

    public function findById(WorkflowTransitionId $id): ?WorkflowTransition
    {
        return $this->entityManager->find(WorkflowTransition::class, $id->value());
    }

    public function findByWorkflowId(WorkflowId $workflowId): array
    {
        return $this->entityManager->getRepository(WorkflowTransition::class)->findBy([
            'workflowId' => $workflowId->value(),
        ]);
    }

    public function existsByName(WorkflowId $workflowId, string $name): bool
    {
        return $this->entityManager->getRepository(WorkflowTransition::class)->findOneBy([
            'workflowId' => $workflowId->value(),
            'name' => $name,
        ]) !== null;
    }
}
