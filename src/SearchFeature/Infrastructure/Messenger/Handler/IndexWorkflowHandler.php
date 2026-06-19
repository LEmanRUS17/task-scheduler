<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\Handler;

use App\SearchFeature\Domain\Port\WorkflowSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexWorkflowMessage;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class IndexWorkflowHandler
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly WorkflowSearchIndexInterface $searchIndex,
    ) {
    }

    public function __invoke(IndexWorkflowMessage $message): void
    {
        $workflow = $this->workflowService->getById($message->workflowId);

        if ($workflow === null) {
            return;
        }

        $this->searchIndex->index(
            $workflow->getId(),
            $workflow->getTitle(),
            $workflow->getDescription() ?? '',
            $workflow->getCreatedBy(),
            $workflow->getCreatedAt(),
        );
    }
}
