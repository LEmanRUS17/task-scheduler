<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTORequest;

use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TeamCreateRequestDTO implements TeamCreateRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(
            min: 1,
            max: 255,
            maxMessage: 'Title must not exceed 255 characters',
        )]
        private readonly string $title,
        private readonly ?string $description = null,
        /** @var string[] */
        #[Assert\All([
            new Assert\Type(type: 'string', message: 'Tag id must be a string'),
            new Assert\NotBlank(message: 'Tag id must not be blank'),
        ])]
        private readonly array $tagIds = [],
        #[Assert\NotBlank(allowNull: true, message: 'Workflow id must not be blank')]
        private readonly ?string $workflowId = null,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTagIds(): array
    {
        return $this->tagIds;
    }

    public function getWorkflowId(): ?string
    {
        return $this->workflowId;
    }
}
