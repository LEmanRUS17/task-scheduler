<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Workflow;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use Symfony\Component\Workflow\Registry;

final class SymfonyTaskWorkflow implements TaskWorkflowInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
    ) {
    }

    public function initialize(Task $task): void
    {
        $workflowId = WorkflowId::fromString($task->getWorkflowDefinitionTitle());
        $initial = $this->statuses->findInitial($workflowId);

        if ($initial === null) {
            throw new \DomainException(
                "No initial status found for workflow '{$task->getWorkflowDefinitionTitle()}'",
            );
        }

        $task->setWorkflowStatus($initial->id()->value());
    }

    public function applyTransition(Task $task, string $transition): void
    {
        $workflow = $this->registry->get($task, $this->resolveWorkflowName($task));

        if (!$workflow->can($task, $transition)) {
            throw new \DomainException(
                "Transition '{$transition}' is not available from status '{$task->getWorkflowStatus()}'",
            );
        }

        $workflow->apply($task, $transition);
    }

    public function canApply(Task $task, string $transition): bool
    {
        return $this->registry->get($task, $this->resolveWorkflowName($task))->can($task, $transition);
    }

    public function getEnabledTransitions(Task $task): array
    {
        return array_map(
            fn($t) => $t->getName(),
            $this->registry->get($task, $this->resolveWorkflowName($task))->getEnabledTransitions($task),
        );
    }

    /**
     * Registry lookups must key on the workflow id, not its title: titles are user-chosen and
     * not unique (e.g. every user's personal default workflow is titled "Базовый"), so using the
     * title here would leave the registry unable to tell same-named workflows apart.
     */
    private function resolveWorkflowName(Task $task): string
    {
        $workflow = $this->workflows->findById(WorkflowId::fromString($task->getWorkflowDefinitionTitle()));

        if ($workflow === null) {
            throw new \DomainException(
                "Workflow '{$task->getWorkflowDefinitionTitle()}' not found",
            );
        }

        return $workflow->id()->value();
    }
}
