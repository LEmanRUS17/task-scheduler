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
        #[Assert\NotBlank(message: 'From status id is required')]
        #[Assert\Uuid(message: 'From status id must be a valid UUID')]
        private readonly string $fromStatusId,
        #[Assert\NotBlank(message: 'To status id is required')]
        #[Assert\Uuid(message: 'To status id must be a valid UUID')]
        private readonly string $toStatusId,
        private readonly ?string $description = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFromStatusId(): string
    {
        return $this->fromStatusId;
    }

    public function getToStatusId(): string
    {
        return $this->toStatusId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
