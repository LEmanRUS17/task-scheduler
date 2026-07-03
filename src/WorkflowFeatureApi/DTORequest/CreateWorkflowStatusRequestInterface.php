<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface CreateWorkflowStatusRequestInterface
{
    public function getLabel(): string;

    public function isInitial(): bool;

    public function isFinal(): bool;
}
