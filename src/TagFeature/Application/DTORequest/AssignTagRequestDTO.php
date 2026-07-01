<?php

declare(strict_types=1);

namespace App\TagFeature\Application\DTORequest;

use Symfony\Component\Validator\Constraints as Assert;

final class AssignTagRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Entity type is required')]
        #[Assert\Choice(choices: ['task', 'team', 'workflow'], message: 'Unknown entity type')]
        private readonly string $entityType,
        #[Assert\NotBlank(message: 'Entity id is required')]
        private readonly string $entityId,
    ) {
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }
}
