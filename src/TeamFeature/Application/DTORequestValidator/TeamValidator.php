<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTORequestValidator;

use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TeamValidator implements TeamValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function validate(TeamCreateRequestInterface $dto): array
    {
        $violations = [];
        $violationList = $this->validator->validate($dto);

        foreach ($violationList as $violation) {
            $violations[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $violations;
    }
}
