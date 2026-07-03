<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface CreateWorkflowRequestInterface extends WorkflowRequestInterface
{
    public function getTitle(): string;

    public function getDescription(): ?string;

    /** @return string[] */
    public function getTagIds(): array;

    /** @return CreateWorkflowStatusRequestInterface[] */
    public function getStatuses(): array;

    /** @return CreateWorkflowTransitionRequestInterface[] */
    public function getTransitions(): array;
}
