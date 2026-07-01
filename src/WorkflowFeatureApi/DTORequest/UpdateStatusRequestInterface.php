<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface UpdateStatusRequestInterface extends WorkflowRequestInterface
{
    public function getLabel(): string;

    public function isFinal(): ?bool;

    public function getDescription(): ?string;
}
