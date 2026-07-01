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
        private readonly ?string $description = null,
        /** @var string[] */
        #[Assert\All([
            new Assert\Type(type: 'string', message: 'Tag id must be a string'),
            new Assert\NotBlank(message: 'Tag id must not be blank'),
        ])]
        private readonly array $tagIds = [],
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTagIds(): array
    {
        return $this->tagIds;
    }
}
