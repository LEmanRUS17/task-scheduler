<?php

declare(strict_types=1);

namespace App\SearchFeatureApi\DTOResponse;

interface WorkflowSearchResultInterface
{
    public function getWorkflowId(): string;

    public function getTitle(): string;
}
