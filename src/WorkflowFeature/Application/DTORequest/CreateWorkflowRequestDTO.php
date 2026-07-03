<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequest;

use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateWorkflowRequestDTO implements CreateWorkflowRequestInterface
{
    /**
     * @param CreateWorkflowStatusRequestDTO[] $statuses
     * @param CreateWorkflowTransitionRequestDTO[] $transitions
     * @param string[] $tagIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(max: 255, maxMessage: 'Title must not exceed 255 characters')]
        private readonly string $title,
        #[Assert\Count(min: 2, minMessage: 'At least 2 statuses are required: one initial and one final')]
        #[Assert\Valid]
        private readonly array $statuses = [],
        #[Assert\Count(min: 1, minMessage: 'At least one transition is required')]
        #[Assert\Valid]
        private readonly array $transitions = [],
        private readonly ?string $description = null,
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

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function getTransitions(): array
    {
        return $this->transitions;
    }
}
