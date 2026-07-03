<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\ValueObject\StatusLabel;

final class NewWorkflowStatus
{
    public function __construct(
        public readonly StatusLabel $label,
        public readonly bool $isInitial,
        public readonly bool $isFinal,
    ) {
    }
}
