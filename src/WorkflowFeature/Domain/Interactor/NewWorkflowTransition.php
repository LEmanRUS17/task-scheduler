<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;

final class NewWorkflowTransition
{
    public function __construct(
        public readonly TransitionName $name,
        public readonly StatusLabel $fromLabel,
        public readonly StatusLabel $toLabel,
    ) {
    }
}
