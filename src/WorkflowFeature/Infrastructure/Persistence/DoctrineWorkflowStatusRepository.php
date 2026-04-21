<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Persistence;

use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineWorkflowStatusRepository implements WorkflowStatusRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(WorkflowStatus $status): void
    {
        $this->entityManager->persist($status);
        $this->entityManager->flush();
    }

    public function findByWorkflowId(WorkflowId $workflowId): array
    {
        return $this->entityManager->getRepository(WorkflowStatus::class)->findBy([
            'workflowId' => $workflowId->value(),
        ]);
    }

    public function findByLabel(WorkflowId $workflowId, string $label): ?WorkflowStatus
    {
        return $this->entityManager->getRepository(WorkflowStatus::class)->findOneBy([
            'workflowId' => $workflowId->value(),
            'label' => $label,
        ]);
    }

    public function hasInitial(WorkflowId $workflowId): bool
    {
        return $this->entityManager->getRepository(WorkflowStatus::class)->findOneBy([
            'workflowId' => $workflowId->value(),
            'isInitial' => true,
        ]) !== null;
    }
}
