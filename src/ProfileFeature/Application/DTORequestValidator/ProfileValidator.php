<?php

declare(strict_types=1);

namespace App\ProfileFeature\Application\DTORequestValidator;

use App\ProfileFeatureApi\DTORequest\UpdateProfileRequestInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProfileValidator implements ProfileValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function validate(UpdateProfileRequestInterface $dto): array
    {
        $violations = [];
        $violationList = $this->validator->validate($dto);

        foreach ($violationList as $violation) {
            $violations[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $violations;
    }
}
