<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Application\DTORequestValidator;

interface SubscriptionValidatorInterface
{
    /** @return array<string, string> */
    public function validate(object $request): array;
}
