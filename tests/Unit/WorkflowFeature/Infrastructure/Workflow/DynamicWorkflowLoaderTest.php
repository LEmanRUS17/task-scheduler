<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Infrastructure\Workflow;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use App\WorkflowFeature\Infrastructure\Workflow\DynamicWorkflowLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Workflow\Registry;

final class DynamicWorkflowLoaderTest extends TestCase
{
    public function testRegistersSameTitledWorkflowsUnderDistinctNamesKeyedById(): void
    {
        $now = new \DateTimeImmutable('2024-01-01 00:00:00');

        // Two different users' personal default workflows, both titled "Базовый" — this is the
        // shape that used to crash Registry::get() with "Too many workflows match this subject".
        $title = WorkflowTitle::fromString('Базовый');
        $workflow1 = Workflow::create(WorkflowId::generate(), $title, 'user-1', $now, true);
        $workflow2 = Workflow::create(WorkflowId::generate(), $title, 'user-2', $now, true);

        $status1 = WorkflowStatus::add(
            WorkflowStatusId::generate(),
            $workflow1->id(),
            StatusLabel::fromString('открыт'),
            true,
            $now,
        );
        $status2 = WorkflowStatus::add(
            WorkflowStatusId::generate(),
            $workflow2->id(),
            StatusLabel::fromString('открыт'),
            true,
            $now,
        );

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findAll')->willReturn([$workflow1, $workflow2]);

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByWorkflowId')->willReturnCallback(
            static fn(WorkflowId $id) => match ($id->value()) {
                $workflow1->id()->value() => [$status1],
                $workflow2->id()->value() => [$status2],
                default => [],
            },
        );

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findByWorkflowId')->willReturn([]);

        $registry = new Registry();
        $loader = new DynamicWorkflowLoader($registry, $workflows, $statuses, $transitions);

        $task1 = $this->makeTask($workflow1->id()->value());
        $task2 = $this->makeTask($workflow2->id()->value());

        $loader->load($this->makeMainRequestEvent());

        $this->assertNotSame(
            $registry->get($task1, $workflow1->id()->value()),
            $registry->get($task2, $workflow2->id()->value()),
        );
    }

    private function makeTask(string $workflowId): Task
    {
        return Task::create(
            TaskId::generate(),
            TaskTitle::fromString('Test task'),
            TaskPriority::NORMAL,
            $workflowId,
            null,
            'user-1',
            new \DateTimeImmutable(),
        );
    }

    private function makeMainRequestEvent(): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
