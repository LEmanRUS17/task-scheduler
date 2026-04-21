<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Event;

use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;

final class WorkflowStatusAdded
{
    public function __construct(
        public readonly WorkflowStatusId $id,
        public readonly WorkflowId $workflowId,
        public readonly StatusLabel $label,
    ) {
    }
}
