<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Entity;

use App\AuditLogFeatureApi\Contract\AuditableInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

final class WorkflowTeam implements AuditableInterface
{
    private string $teamId;
    private string $workflowId;
    private \DateTimeImmutable $attachedAt;

    private function __construct(string $teamId, WorkflowId $workflowId, \DateTimeImmutable $attachedAt)
    {
        $this->teamId = $teamId;
        $this->workflowId = $workflowId->value();
        $this->attachedAt = $attachedAt;
    }

    public static function attach(WorkflowId $workflowId, string $teamId, \DateTimeImmutable $attachedAt): self
    {
        return new self($teamId, $workflowId, $attachedAt);
    }

    public function teamId(): string
    {
        return $this->teamId;
    }

    public function workflowId(): WorkflowId
    {
        return WorkflowId::fromString($this->workflowId);
    }

    public function attachedAt(): \DateTimeImmutable
    {
        return $this->attachedAt;
    }
}
