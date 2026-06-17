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
        private readonly string $fromStatusId,
        private readonly string $toStatusId,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?string $description = null,
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

    public function getFromStatusId(): string
    {
        return $this->fromStatusId;
    }

    public function getToStatusId(): string
    {
        return $this->toStatusId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
