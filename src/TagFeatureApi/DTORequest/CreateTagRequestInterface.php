<?php

declare(strict_types=1);

namespace App\TagFeatureApi\DTORequest;

interface CreateTagRequestInterface
{
    public function getName(): string;

    public function getColor(): string;

    public function getDescription(): ?string;
}
