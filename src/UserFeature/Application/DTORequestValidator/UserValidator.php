<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DTORequestValidator;

use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserValidator implements UserValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function validate(RegisterUserRequestInterface $dto): array
    {
        $violations = [];
        $violationList = $this->validator->validate($dto);

        foreach ($violationList as $violation) {
            $violations[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $violations;
    }
}
