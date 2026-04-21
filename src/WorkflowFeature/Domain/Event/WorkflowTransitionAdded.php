<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Event;

use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

final class WorkflowTransitionAdded
{
    public function __construct(
        public readonly WorkflowTransitionId $id,
        public readonly WorkflowId $workflowId,
        public readonly TransitionName $name,
    ) {
    }
}
