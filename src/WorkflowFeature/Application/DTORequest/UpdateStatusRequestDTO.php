<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTORequest;

use App\WorkflowFeatureApi\DTORequest\UpdateStatusRequestInterface;

final class UpdateStatusRequestDTO implements UpdateStatusRequestInterface
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
