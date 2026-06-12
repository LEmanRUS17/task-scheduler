<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTORequest;

use App\TeamFeatureApi\DTORequest\TeamUpdateRequestInterface;

final class TeamUpdateRequestDTO implements TeamUpdateRequestInterface
{
    public function __construct(
        private readonly ?string $description = null,
    ) {
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
