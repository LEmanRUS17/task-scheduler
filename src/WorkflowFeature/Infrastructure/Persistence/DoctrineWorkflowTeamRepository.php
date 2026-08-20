<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Persistence;

use App\WorkflowFeature\Domain\Entity\WorkflowTeam;
use App\WorkflowFeature\Domain\Repository\WorkflowTeamRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineWorkflowTeamRepository implements WorkflowTeamRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(WorkflowTeam $link): void
    {
        $this->entityManager->persist($link);
        $this->entityManager->flush();
    }

    public function findByWorkflowIdAndTeamId(WorkflowId $workflowId, string $teamId): ?WorkflowTeam
    {
        return $this->entityManager->getRepository(WorkflowTeam::class)->findOneBy([
            'workflowId' => $workflowId->value(),
            'teamId' => $teamId,
        ]);
    }

    public function findByTeamId(string $teamId): array
    {
        return $this->entityManager->getRepository(WorkflowTeam::class)->findBy([
            'teamId' => $teamId,
        ]);
    }

    public function findByWorkflowId(WorkflowId $workflowId): array
    {
        return $this->entityManager->getRepository(WorkflowTeam::class)->findBy([
            'workflowId' => $workflowId->value(),
        ]);
    }

    public function delete(WorkflowTeam $link): void
    {
        $this->entityManager->remove($link);
        $this->entityManager->flush();
    }
}
