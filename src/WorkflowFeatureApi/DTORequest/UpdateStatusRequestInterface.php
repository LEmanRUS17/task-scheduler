<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface UpdateStatusRequestInterface extends WorkflowRequestInterface
{
    public function getDescription(): ?string;
}
