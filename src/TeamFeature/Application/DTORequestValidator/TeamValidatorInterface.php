<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTORequestValidator;

use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;

interface TeamValidatorInterface
{
    /** @return array<string, string[]> */
    public function validate(TeamCreateRequestInterface $dto): array;
}
