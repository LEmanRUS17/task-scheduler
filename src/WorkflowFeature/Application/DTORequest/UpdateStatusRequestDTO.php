<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequest;

use App\WorkflowFeatureApi\DTORequest\UpdateStatusRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateStatusRequestDTO implements UpdateStatusRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Label is required')]
        #[Assert\Regex(pattern: '/^\S+$/', message: 'Label must not contain whitespace')]
        #[Assert\Length(max: 100, maxMessage: 'Label must not exceed 100 characters')]
        private readonly string $label,
        private readonly ?bool $isFinal = null,
        private readonly ?string $description = null,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isFinal(): ?bool
    {
        return $this->isFinal;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
