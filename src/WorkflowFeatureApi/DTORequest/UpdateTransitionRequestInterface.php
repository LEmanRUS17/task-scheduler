<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTORequest;

interface UpdateTransitionRequestInterface extends WorkflowRequestInterface
{
    public function getName(): string;

    public function getFromStatusId(): string;

    public function getToStatusId(): string;

    public function getDescription(): ?string;
}
