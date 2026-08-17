<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

final class CreateWorkflowInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Creates a workflow together with the whole status/transition graph required to use
     * it right away: exactly one initial status, at least one final status reachable from
     * it, and the transitions connecting them (branches allowed).
     *
     * @param list<NewWorkflowStatus> $statuses
     * @param list<NewWorkflowTransition> $transitions
     */
    public function create(
        WorkflowTitle $title,
        string $createdBy,
        array $statuses,
        array $transitions,
        bool $isDefault = false,
    ): Workflow {
        $this->assertValidGraph($statuses, $transitions);

        $now = $this->clock->now();
        $id = WorkflowId::generate();
        $workflow = Workflow::create($id, $title, $createdBy, $now, $isDefault);

        $statusEntities = [];
        foreach ($statuses as $status) {
            $statusEntities[$status->label->value()] = WorkflowStatus::add(
                WorkflowStatusId::generate(),
                $id,
                $status->label,
                $status->isInitial,
                $now,
                $status->isFinal,
            );
        }

        $transitionEntities = [];
        foreach ($transitions as $transition) {
            $transitionEntities[] = WorkflowTransition::add(
                WorkflowTransitionId::generate(),
                $id,
                $transition->name,
                $statusEntities[$transition->fromLabel->value()]->id(),
                $statusEntities[$transition->toLabel->value()]->id(),
                $now,
            );
        }

        $this->workflows->save($workflow);

        $events = $workflow->pullDomainEvents();

        foreach ($statusEntities as $status) {
            $this->statuses->save($status);
            $events = [...$events, ...$status->pullDomainEvents()];
        }

        foreach ($transitionEntities as $transition) {
            $this->transitions->save($transition);
            $events = [...$events, ...$transition->pullDomainEvents()];
        }

        $this->eventDispatcher->dispatch(...$events);

        return $workflow;
    }

    /**
     * @param list<NewWorkflowStatus> $statuses
     * @param list<NewWorkflowTransition> $transitions
     */
    private function assertValidGraph(array $statuses, array $transitions): void
    {
        if (count($statuses) < 2) {
            throw new \DomainException('Workflow must have at least 2 statuses: one initial and one final');
        }

        $labels = [];
        $initialLabel = null;
        $finalLabels = [];

        foreach ($statuses as $status) {
            $label = $status->label->value();

            if (isset($labels[$label])) {
                throw new \DomainException("Status \"{$label}\" is duplicated");
            }
            $labels[$label] = true;

            if ($status->isInitial) {
                if ($initialLabel !== null) {
                    throw new \DomainException('Workflow must have exactly one initial status');
                }
                $initialLabel = $label;
            }

            if ($status->isFinal) {
                $finalLabels[$label] = true;
            }
        }

        if ($initialLabel === null) {
            throw new \DomainException('Workflow must have exactly one initial status');
        }

        if ($finalLabels === []) {
            throw new \DomainException('Workflow must have at least one final status');
        }

        if ($transitions === []) {
            throw new \DomainException('Workflow must have at least one transition');
        }

        $names = [];
        $adjacency = [];

        foreach ($transitions as $transition) {
            $name = $transition->name->value();

            if (isset($names[$name])) {
                throw new \DomainException("Transition \"{$name}\" is duplicated");
            }
            $names[$name] = true;

            $from = $transition->fromLabel->value();
            $to = $transition->toLabel->value();

            if (!isset($labels[$from])) {
                throw new \DomainException("Transition \"{$name}\" references unknown status \"{$from}\"");
            }

            if (!isset($labels[$to])) {
                throw new \DomainException("Transition \"{$name}\" references unknown status \"{$to}\"");
            }

            $adjacency[$from][] = $to;
        }

        if (!$this->canReachFinalStatus($initialLabel, $finalLabels, $adjacency)) {
            throw new \DomainException('No final status is reachable from the initial status');
        }
    }

    /**
     * @param array<string, true> $finalLabels
     * @param array<string, list<string>> $adjacency
     */
    private function canReachFinalStatus(string $initialLabel, array $finalLabels, array $adjacency): bool
    {
        $visited = [$initialLabel => true];
        $queue = [$initialLabel];

        while ($queue !== []) {
            $current = array_shift($queue);

            if (isset($finalLabels[$current])) {
                return true;
            }

            foreach ($adjacency[$current] ?? [] as $next) {
                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        return false;
    }
}
