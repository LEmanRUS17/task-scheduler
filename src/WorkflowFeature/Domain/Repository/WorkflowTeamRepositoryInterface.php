<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Repository;

use App\WorkflowFeature\Domain\Entity\WorkflowTeam;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

interface WorkflowTeamRepositoryInterface
{
    public function save(WorkflowTeam $link): void;

    public function findByWorkflowIdAndTeamId(WorkflowId $workflowId, string $teamId): ?WorkflowTeam;

    /** @return WorkflowTeam[] */
    public function findByTeamId(string $teamId): array;

    /** @return WorkflowTeam[] */
    public function findByWorkflowId(WorkflowId $workflowId): array;

    public function delete(WorkflowTeam $link): void;
}
