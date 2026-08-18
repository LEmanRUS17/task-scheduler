<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTOResponse;

use App\WorkflowFeatureApi\DTOResponse\WorkflowListMiniItemResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowListMiniResponseInterface;

final class WorkflowListMiniResponseDTO implements WorkflowListMiniResponseInterface
{
    /** @param list<WorkflowListMiniItemResponseInterface> $transitions */
    public function __construct(
        private readonly array $transitions,
        private readonly int $count,
    ) {
    }

    public function getTransitions(): array
    {
        return $this->transitions;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
