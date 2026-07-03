<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequest;

use App\WorkflowFeatureApi\DTORequest\CreateWorkflowStatusRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateWorkflowStatusRequestDTO implements CreateWorkflowStatusRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Status label is required')]
        #[Assert\Regex(pattern: '/^\S+$/', message: 'Status label must not contain whitespace')]
        #[Assert\Length(max: 100, maxMessage: 'Status label must not exceed 100 characters')]
        private readonly string $label = '',
        private readonly bool $isInitial = false,
        private readonly bool $isFinal = false,
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

    public function isFinal(): bool
    {
        return $this->isFinal;
    }
}
