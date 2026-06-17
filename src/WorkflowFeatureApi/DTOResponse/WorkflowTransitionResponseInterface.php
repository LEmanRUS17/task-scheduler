<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTOResponse;

interface WorkflowTransitionResponseInterface
{
    public function getId(): string;

    public function getWorkflowId(): string;

    public function getName(): string;

    public function getFromStatusId(): string;

    public function getToStatusId(): string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getDescription(): ?string;
}
