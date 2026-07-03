<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Interactor\CreateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\NewWorkflowStatus;
use App\WorkflowFeature\Domain\Interactor\NewWorkflowTransition;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class CreateWorkflowInteractorTest extends TestCase
{
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));
    }

    private function buildInteractor(
        WorkflowRepositoryInterface $workflows,
        ?WorkflowStatusRepositoryInterface $statuses = null,
        ?WorkflowTransitionRepositoryInterface $transitions = null,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): CreateWorkflowInteractor {
        return new CreateWorkflowInteractor(
            $workflows,
            $statuses ?? $this->createStub(WorkflowStatusRepositoryInterface::class),
            $transitions ?? $this->createStub(WorkflowTransitionRepositoryInterface::class),
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    private function newStatus(string $label, bool $isInitial = false, bool $isFinal = false): NewWorkflowStatus
    {
        return new NewWorkflowStatus(StatusLabel::fromString($label), $isInitial, $isFinal);
    }

    private function newTransition(string $name, string $from, string $to): NewWorkflowTransition
    {
        return new NewWorkflowTransition(
            TransitionName::fromString($name),
            StatusLabel::fromString($from),
            StatusLabel::fromString($to),
        );
    }

    /** @return list<NewWorkflowStatus> */
    private function linearStatuses(): array
    {
        return [
            $this->newStatus('open', isInitial: true),
            $this->newStatus('done', isFinal: true),
        ];
    }

    /** @return list<NewWorkflowTransition> */
    private function linearTransitions(): array
    {
        return [$this->newTransition('close', 'open', 'done')];
    }

    /**
     * @param list<NewWorkflowStatus>|null $statuses
     * @param list<NewWorkflowTransition>|null $transitions
     */
    private function create(
        CreateWorkflowInteractor $interactor,
        ?array $statuses = null,
        ?array $transitions = null,
    ): Workflow {
        return $interactor->create(
            WorkflowTitle::fromString('My Workflow'),
            'user-1',
            $statuses ?? $this->linearStatuses(),
            $transitions ?? $this->linearTransitions(),
        );
    }

    public function testCreateSavesWorkflow(): void
    {
        $workflows = $this->createMock(WorkflowRepositoryInterface::class);
        $workflows->expects($this->once())->method('save');

        $this->create($this->buildInteractor($workflows));
    }

    public function testCreateSavesInitialAndFinalStatuses(): void
    {
        $statuses = $this->createMock(WorkflowStatusRepositoryInterface::class);
        $saved = [];
        $statuses->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($status) use (&$saved): void {
                $saved[$status->label()->value()] = $status;
            });

        $this->create($this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class), $statuses));

        $this->assertTrue($saved['open']->isInitial());
        $this->assertFalse($saved['open']->isFinal());
        $this->assertFalse($saved['done']->isInitial());
        $this->assertTrue($saved['done']->isFinal());
    }

    public function testCreateSavesTransitionsBetweenStatuses(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $transitions = $this->createMock(WorkflowTransitionRepositoryInterface::class);
        $transitions->expects($this->once())->method('save');

        $this->create($this->buildInteractor(
            $this->createStub(WorkflowRepositoryInterface::class),
            $statuses,
            $transitions,
        ));
    }

    public function testCreateSupportsBranchingGraph(): void
    {
        $statuses = [
            $this->newStatus('open', isInitial: true),
            $this->newStatus('in_progress'),
            $this->newStatus('review'),
            $this->newStatus('done', isFinal: true),
            $this->newStatus('rejected', isFinal: true),
        ];
        $transitions = [
            $this->newTransition('start_progress', 'open', 'in_progress'),
            $this->newTransition('submit_review', 'in_progress', 'review'),
            $this->newTransition('approve', 'review', 'done'),
            $this->newTransition('reject', 'review', 'rejected'),
            $this->newTransition('rework', 'review', 'in_progress'),
        ];

        $statusRepo = $this->createMock(WorkflowStatusRepositoryInterface::class);
        $statusRepo->expects($this->exactly(5))->method('save');
        $transitionRepo = $this->createMock(WorkflowTransitionRepositoryInterface::class);
        $transitionRepo->expects($this->exactly(5))->method('save');

        $workflow = $this->create(
            $this->buildInteractor(
                $this->createStub(WorkflowRepositoryInterface::class),
                $statusRepo,
                $transitionRepo,
            ),
            $statuses,
            $transitions,
        );

        $this->assertInstanceOf(Workflow::class, $workflow);
    }

    public function testCreateDispatchesEventsForWorkflowStatusesAndTransitions(): void
    {
        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')->with(
            $this->isInstanceOf(\App\WorkflowFeature\Domain\Event\WorkflowCreated::class),
            $this->isInstanceOf(\App\WorkflowFeature\Domain\Event\WorkflowStatusAdded::class),
            $this->isInstanceOf(\App\WorkflowFeature\Domain\Event\WorkflowStatusAdded::class),
            $this->isInstanceOf(\App\WorkflowFeature\Domain\Event\WorkflowTransitionAdded::class),
        );

        $this->create($this->buildInteractor(
            $this->createStub(WorkflowRepositoryInterface::class),
            dispatcher: $dispatcher,
        ));
    }

    public function testCreateReturnsWorkflow(): void
    {
        $workflow = $this->create($this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)));

        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertSame('My Workflow', $workflow->title()->value());
        $this->assertSame('user-1', $workflow->createdBy());
    }

    public function testCreateThrowsWhenFewerThanTwoStatuses(): void
    {
        $this->expectException(\DomainException::class);

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            [$this->newStatus('open', isInitial: true)],
            [],
        );
    }

    public function testCreateThrowsWhenNoInitialStatus(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/exactly one initial/');

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            [$this->newStatus('open'), $this->newStatus('done', isFinal: true)],
            [$this->newTransition('close', 'open', 'done')],
        );
    }

    public function testCreateThrowsWhenMultipleInitialStatuses(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/exactly one initial/');

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            [
                $this->newStatus('open', isInitial: true),
                $this->newStatus('also_open', isInitial: true),
                $this->newStatus('done', isFinal: true),
            ],
            [$this->newTransition('close', 'open', 'done')],
        );
    }

    public function testCreateThrowsWhenNoFinalStatus(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/at least one final/');

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            [$this->newStatus('open', isInitial: true), $this->newStatus('done')],
            [$this->newTransition('close', 'open', 'done')],
        );
    }

    public function testCreateThrowsWhenDuplicateStatusLabels(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/duplicated/');

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            [
                $this->newStatus('open', isInitial: true),
                $this->newStatus('open'),
                $this->newStatus('done', isFinal: true),
            ],
            [$this->newTransition('close', 'open', 'done')],
        );
    }

    public function testCreateThrowsWhenNoTransitions(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/at least one transition/');

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            $this->linearStatuses(),
            [],
        );
    }

    public function testCreateThrowsWhenDuplicateTransitionNames(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/duplicated/');

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            [
                $this->newStatus('open', isInitial: true),
                $this->newStatus('in_progress'),
                $this->newStatus('done', isFinal: true),
            ],
            [
                $this->newTransition('go', 'open', 'in_progress'),
                $this->newTransition('go', 'in_progress', 'done'),
            ],
        );
    }

    public function testCreateThrowsWhenTransitionReferencesUnknownStatus(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/unknown status/');

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            $this->linearStatuses(),
            [$this->newTransition('close', 'open', 'nonexistent')],
        );
    }

    public function testCreateThrowsWhenFinalStatusIsUnreachableFromInitial(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/reachable/');

        $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            [
                $this->newStatus('open', isInitial: true),
                $this->newStatus('orphan'),
                $this->newStatus('done', isFinal: true),
            ],
            // "done" only reachable from "orphan", which is itself unreachable from "open"
            [$this->newTransition('finish', 'orphan', 'done')],
        );
    }

    public function testCreateSucceedsWhenFinalStatusReachableThroughBranch(): void
    {
        $workflow = $this->create(
            $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class)),
            [
                $this->newStatus('open', isInitial: true),
                $this->newStatus('path_a'),
                $this->newStatus('path_b'),
                $this->newStatus('done', isFinal: true),
            ],
            [
                $this->newTransition('go_a', 'open', 'path_a'),
                $this->newTransition('go_b', 'open', 'path_b'),
                $this->newTransition('finish_b', 'path_b', 'done'),
            ],
        );

        $this->assertInstanceOf(Workflow::class, $workflow);
    }
}
