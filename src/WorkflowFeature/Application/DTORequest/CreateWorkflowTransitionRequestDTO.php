<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequest;

use App\WorkflowFeatureApi\DTORequest\CreateWorkflowTransitionRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateWorkflowTransitionRequestDTO implements CreateWorkflowTransitionRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Regex(pattern: '/^\S+$/', message: 'Name must not contain whitespace')]
        #[Assert\Length(max: 100, maxMessage: 'Name must not exceed 100 characters')]
        private readonly string $name = '',
        #[Assert\NotBlank(message: 'From status label is required')]
        private readonly string $from = '',
        #[Assert\NotBlank(message: 'To status label is required')]
        private readonly string $to = '',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }
}
