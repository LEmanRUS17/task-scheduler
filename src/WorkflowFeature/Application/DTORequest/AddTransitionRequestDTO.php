<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequest;

use App\WorkflowFeatureApi\DTORequest\AddTransitionRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class AddTransitionRequestDTO implements AddTransitionRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Regex(pattern: '/^\S+$/', message: 'Name must not contain whitespace')]
        #[Assert\Length(max: 100, maxMessage: 'Name must not exceed 100 characters')]
        private readonly string $name,
        #[Assert\NotBlank(message: 'From status label is required')]
        #[Assert\Regex(pattern: '/^\S+$/', message: 'From status label must not contain whitespace')]
        private readonly string $fromStatusLabel,
        #[Assert\NotBlank(message: 'To status label is required')]
        #[Assert\Regex(pattern: '/^\S+$/', message: 'To status label must not contain whitespace')]
        private readonly string $toStatusLabel,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFromStatusLabel(): string
    {
        return $this->fromStatusLabel;
    }

    public function getToStatusLabel(): string
    {
        return $this->toStatusLabel;
    }
}
