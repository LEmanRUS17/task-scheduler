<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTOResponse;

use App\WorkflowFeatureApi\DTOResponse\WorkflowTransitionResponseInterface;

final class WorkflowTransitionResponseDTO implements WorkflowTransitionResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $workflowId,
        private readonly string $name,
        private readonly string $fromStatusLabel,
        private readonly string $toStatusLabel,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getFromStatusLabel(): string
    {
        return $this->fromStatusLabel;
    }

    public function getToStatusLabel(): string
    {
        return $this->toStatusLabel;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
