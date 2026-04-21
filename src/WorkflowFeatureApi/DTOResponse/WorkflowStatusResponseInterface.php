<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTOResponse;

interface WorkflowStatusResponseInterface
{
    public function getId(): string;

    public function getWorkflowId(): string;

    public function getLabel(): string;

    public function isInitial(): bool;

    public function getCreatedAt(): \DateTimeImmutable;
}
