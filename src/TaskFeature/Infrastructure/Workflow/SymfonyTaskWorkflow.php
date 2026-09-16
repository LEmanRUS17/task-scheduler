<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Workflow;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Infrastructure\Workflow\DynamicWorkflowLoader;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

final class SymfonyTaskWorkflow implements TaskWorkflowInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly DynamicWorkflowLoader $workflowLoader,
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
        $workflow = $this->getWorkflow($task);

        if (!$workflow->can($task, $transition)) {
            throw new \DomainException(
                "Transition '{$transition}' is not available from status '{$task->getWorkflowStatus()}'",
            );
        }

        $workflow->apply($task, $transition);
    }

    public function canApply(Task $task, string $transition): bool
    {
        return $this->getWorkflow($task)->can($task, $transition);
    }

    public function getEnabledTransitions(Task $task): array
    {
        return array_map(
            fn($t) => $t->getName(),
            $this->getWorkflow($task)->getEnabledTransitions($task),
        );
    }

    /**
     * Ensures the DB-backed workflows are registered before every lookup: this is
     * the only reliable trigger point, since it must work whether the call comes
     * from an HTTP request or a console worker (see DynamicWorkflowLoader docblock).
     */
    private function getWorkflow(Task $task): WorkflowInterface
    {
        $this->workflowLoader->ensureLoaded();

        return $this->registry->get($task, $this->resolveWorkflowName($task));
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
