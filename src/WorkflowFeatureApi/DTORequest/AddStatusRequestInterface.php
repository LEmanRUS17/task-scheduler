<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface AddStatusRequestInterface extends WorkflowRequestInterface
{
    public function getLabel(): string;

    public function isInitial(): bool;

    public function isFinal(): bool;

    public function getDescription(): ?string;
}
