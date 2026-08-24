<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\EventListener;

use App\UserFeature\Domain\Event\UserRegistered;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class UserRegisteredListener
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {
    }

    public function __invoke(UserRegistered $event): void
    {
        $this->workflowService->createDefaultForUser($event->userId->value());
    }
}
