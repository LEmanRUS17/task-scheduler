<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequest;

use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateWorkflowRequestDTO implements CreateWorkflowRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(max: 255, maxMessage: 'Title must not exceed 255 characters')]
        private readonly string $title,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
