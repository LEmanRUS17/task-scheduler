<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Workflow;

use App\WorkflowFeatureApi\Contract\WorkflowSubjectInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

final class WorkflowGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return ['workflow.guard' => 'onGuard'];
    }

    public function onGuard(GuardEvent $event): void
    {
        if (!$event->getSubject() instanceof WorkflowSubjectInterface) {
            return;
        }

        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $event->setBlocked(true, 'Authentication required');
        }
    }
}
