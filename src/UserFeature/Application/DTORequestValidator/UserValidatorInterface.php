<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DTORequestValidator;

use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;

interface UserValidatorInterface
{
    /** @return array<string, string[]> */
    public function validate(RegisterUserRequestInterface $dto): array;
}
