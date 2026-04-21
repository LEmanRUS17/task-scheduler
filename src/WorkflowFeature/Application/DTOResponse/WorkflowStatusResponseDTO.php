<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTOResponse;

use App\WorkflowFeatureApi\DTOResponse\WorkflowStatusResponseInterface;

final class WorkflowStatusResponseDTO implements WorkflowStatusResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $workflowId,
        private readonly string $label,
        private readonly bool $isInitial,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isInitial(): bool
    {
        return $this->isInitial;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
