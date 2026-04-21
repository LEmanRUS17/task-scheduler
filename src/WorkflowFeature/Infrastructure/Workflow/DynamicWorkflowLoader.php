<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Workflow;

use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeatureApi\Contract\WorkflowSubjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;

final class DynamicWorkflowLoader implements EventSubscriberInterface
{
    private bool $loaded = false;

    public function __construct(
        private readonly Registry $registry,
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['load', 255]];
    }

    public function load(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $this->loaded) {
            return;
        }

        foreach ($this->workflows->findAll() as $workflow) {
            $statusList = $this->statuses->findByWorkflowId($workflow->id());
            $transitionList = $this->transitions->findByWorkflowId($workflow->id());

            $places = array_map(fn(WorkflowStatus $s) => $s->label()->value(), $statusList);

            $initialPlace = null;
            foreach ($statusList as $status) {
                if ($status->isInitial()) {
                    $initialPlace = $status->label()->value();
                    break;
                }
            }

            $sfTransitions = array_map(
                fn(WorkflowTransition $t) => new Transition(
                    $t->name()->value(),
                    $t->fromStatusLabel()->value(),
                    $t->toStatusLabel()->value(),
                ),
                $transitionList,
            );

            $sfDefinition = new Definition(
                $places,
                $sfTransitions,
                $initialPlace !== null ? [$initialPlace] : [],
            );

            $markingStore = new MethodMarkingStore(true, 'workflowStatus');

            $stateMachine = new StateMachine(
                $sfDefinition,
                $markingStore,
                name: $workflow->title()->value(),
            );

            $this->registry->addWorkflow(
                $stateMachine,
                new InstanceOfSupportStrategy(WorkflowSubjectInterface::class),
            );
        }

        $this->loaded = true;
    }
}
