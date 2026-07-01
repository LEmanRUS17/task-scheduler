<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTORequest;

interface TeamCreateRequestInterface
{
    public function getTitle(): string;

    public function setTitle(string $title): void;

    public function getDescription(): ?string;

    /** @return string[] */
    public function getTagIds(): array;
}
