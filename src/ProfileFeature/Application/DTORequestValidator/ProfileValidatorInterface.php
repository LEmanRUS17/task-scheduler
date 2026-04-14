<?php

declare(strict_types=1);

namespace App\ProfileFeature\Application\DTORequestValidator;

use App\ProfileFeatureApi\DTORequest\UpdateProfileRequestInterface;

interface ProfileValidatorInterface
{
    /** @return array<string, string[]> */
    public function validate(UpdateProfileRequestInterface $dto): array;
}
