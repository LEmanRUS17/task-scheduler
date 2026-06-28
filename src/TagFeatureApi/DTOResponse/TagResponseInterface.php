<?php

declare(strict_types=1);

namespace App\TagFeatureApi\DTOResponse;

interface TagResponseInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getColor(): string;

    public function getOwnerId(): string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getDescription(): ?string;
}
