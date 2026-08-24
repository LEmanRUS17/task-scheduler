<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTOResponse;

use App\WorkflowFeatureApi\DTOResponse\WorkflowListMiniItemResponseInterface;

final class WorkflowListMiniItemResponseDTO implements WorkflowListMiniItemResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
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
}
