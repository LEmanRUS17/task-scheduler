<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTORequest;

interface TeamUpdateRequestInterface
{
    public function getDescription(): ?string;
}
