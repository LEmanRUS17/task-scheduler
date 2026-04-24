<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTORequestValidator;

use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;

interface TaskValidatorInterface
{
    /** @return array<string, string[]> */
    public function validate(TaskCreateRequestInterface $dto): array;
}
