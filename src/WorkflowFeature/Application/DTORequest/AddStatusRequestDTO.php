<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequest;

use App\WorkflowFeatureApi\DTORequest\AddStatusRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class AddStatusRequestDTO implements AddStatusRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Label is required')]
        #[Assert\Regex(pattern: '/^\S+$/', message: 'Label must not contain whitespace')]
        #[Assert\Length(max: 100, maxMessage: 'Label must not exceed 100 characters')]
        private readonly string $label,
        private readonly bool $isInitial = false,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isInitial(): bool
    {
        return $this->isInitial;
    }
}
