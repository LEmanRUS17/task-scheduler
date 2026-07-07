<?php

declare(strict_types=1);

namespace App\CommentFeature\Application\DTORequestValidator;

use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CommentValidator implements CommentValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return array<string, list<string>> */
    public function validate(object $dto): array
    {
        $violations = [];
        $violationList = $this->validator->validate($dto);

        foreach ($violationList as $violation) {
            $violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        return $violations;
    }
}
