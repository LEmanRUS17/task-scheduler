<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequestValidator;

use App\WorkflowFeatureApi\DTORequest\WorkflowRequestInterface;

interface WorkflowValidatorInterface
{
    /** @return array<string, list<string>> */
    public function validate(WorkflowRequestInterface $dto): array;
}
