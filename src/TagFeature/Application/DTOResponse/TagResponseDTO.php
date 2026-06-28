<?php

declare(strict_types=1);

namespace App\TagFeature\Application\DTOResponse;

use App\TagFeatureApi\DTOResponse\TagResponseInterface;

final class TagResponseDTO implements TagResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $color,
        private readonly string $ownerId,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?string $description = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
