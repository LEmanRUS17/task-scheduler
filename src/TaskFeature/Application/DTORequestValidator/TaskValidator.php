<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTORequestValidator;

use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TaskValidator implements TaskValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return array<string, string[]> */
    public function validate(TaskCreateRequestInterface $dto): array
    {
        $violations = [];
        $violationList = $this->validator->validate($dto);

        foreach ($violationList as $violation) {
            $violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        return $violations;
    }
}
