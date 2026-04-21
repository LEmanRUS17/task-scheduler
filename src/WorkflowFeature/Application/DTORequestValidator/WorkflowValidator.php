<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequestValidator;

use App\WorkflowFeatureApi\DTORequest\WorkflowRequestInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WorkflowValidator implements WorkflowValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return array<string, list<string>> */
    public function validate(WorkflowRequestInterface $dto): array
    {
        $violations = [];
        $violationList = $this->validator->validate($dto);

        foreach ($violationList as $violation) {
            $violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        return $violations;
    }
}
