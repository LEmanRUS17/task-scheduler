<?php

declare(strict_types=1);

namespace App\SearchFeature\Application\DTOResponse;

use App\SearchFeatureApi\DTOResponse\WorkflowSearchResultInterface;

final class WorkflowSearchResult implements WorkflowSearchResultInterface
{
    public function __construct(
        private readonly string $workflowId,
        private readonly string $title,
    ) {
    }

    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
