<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface UpdateTransitionRequestInterface extends WorkflowRequestInterface
{
    public function getName(): string;

    public function getFromStatusLabel(): string;

    public function getToStatusLabel(): string;

    public function getDescription(): ?string;
}
