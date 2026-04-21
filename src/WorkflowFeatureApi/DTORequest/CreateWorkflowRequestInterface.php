<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface CreateWorkflowRequestInterface extends WorkflowRequestInterface
{
    public function getTitle(): string;
}
