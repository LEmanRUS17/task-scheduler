<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\Contract;

interface WorkflowSubjectInterface
{
    public function getWorkflowStatus(): string;

    public function setWorkflowStatus(string $status): void;

    public function getWorkflowDefinitionTitle(): string;
}
