<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Application\Exception;

final class ValidationException extends \RuntimeException
{
    /** @param array<string, string> $violations */
    public function __construct(private readonly array $violations)
    {
        parent::__construct('Validation failed');
    }

    /** @return array<string, string> */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
