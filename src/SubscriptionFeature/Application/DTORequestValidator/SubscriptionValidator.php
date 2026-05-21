<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Application\DTORequestValidator;

use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SubscriptionValidator implements SubscriptionValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function validate(object $request): array
    {
        $violations = $this->validator->validate($request);
        $errors = [];

        foreach ($violations as $violation) {
            $field = ltrim($violation->getPropertyPath(), '.');
            $errors[$field] = $violation->getMessage();
        }

        return $errors;
    }
}
