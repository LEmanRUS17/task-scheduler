<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTOResponse;

interface WorkflowListMiniResponseInterface
{
    /** @return list<WorkflowListMiniItemResponseInterface> */
    public function getTransitions(): array;

    public function getCount(): int;
}
