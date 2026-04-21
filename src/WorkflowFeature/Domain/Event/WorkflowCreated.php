<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Event;

use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;

final class WorkflowCreated
{
    public function __construct(
        public readonly WorkflowId $id,
        public readonly WorkflowTitle $title,
        public readonly string $createdBy,
    ) {
    }
}
