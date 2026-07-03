<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface CreateWorkflowTransitionRequestInterface
{
    public function getName(): string;

    public function getFrom(): string;

    public function getTo(): string;
}
