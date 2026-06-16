<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface UpdateWorkflowRequestInterface extends WorkflowRequestInterface
{
    public function getTitle(): string;

    public function getDescription(): ?string;
}
