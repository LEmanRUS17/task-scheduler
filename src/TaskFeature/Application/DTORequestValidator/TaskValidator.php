<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTORequestValidator;

use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;
use App\TaskFeatureApi\DTORequest\TaskUpdateRequestInterface;
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
        return $this->collectViolations($dto);
    }

    /** @return array<string, string[]> */
    public function validateUpdate(TaskUpdateRequestInterface $dto): array
    {
        return $this->collectViolations($dto);
    }

    /** @return array<string, string[]> */
    private function collectViolations(object $dto): array
    {
        $violations = [];

        foreach ($this->validator->validate($dto) as $violation) {
            $violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        return $violations;
    }
}
