<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Application\ApiService;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\WorkflowFeature\Application\ApiService\WorkflowApiService;
use App\WorkflowFeature\Application\DataMapper\WorkflowDataMapper;
use App\WorkflowFeature\Application\DTORequestValidator\WorkflowValidatorInterface;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Interactor\AttachWorkflowToTeamInteractor;
use App\WorkflowFeature\Domain\Interactor\CreateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\DetachWorkflowFromTeamInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTeamRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use PHPUnit\Framework\TestCase;

final class WorkflowApiServiceListTransactionByWorkflowTest extends TestCase
{
    public function testReturnsTransitionOptionsWithCount(): void
    {
        $transitions = [
            $this->makeTransition('11111111-1111-4111-8111-111111111111', 'Close'),
            $this->makeTransition('22222222-2222-4222-8222-222222222222', 'Reopen'),
        ];

        $transitionRepository = $this->createMock(WorkflowTransitionRepositoryInterface::class);
        $transitionRepository->expects($this->once())
            ->method('findByWorkflowId')
            ->willReturn($transitions);

        $workflowId = '33333333-3333-4333-8333-333333333333';
        $result = $this->makeService($transitionRepository)->listTransactionByWorkflow($workflowId);

        $this->assertSame(2, $result->getCount());
        $this->assertSame(
            ['11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222'],
            array_map(static fn($t) => $t->getId(), $result->getTransitions()),
        );
        $this->assertSame(['Close', 'Reopen'], array_map(static fn($t) => $t->getName(), $result->getTransitions()));
    }

    public function testReturnsEmptyOptionsWithZeroCountWhenWorkflowHasNoTransitions(): void
    {
        $transitionRepository = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitionRepository->method('findByWorkflowId')->willReturn([]);

        $workflowId = '33333333-3333-4333-8333-333333333333';
        $result = $this->makeService($transitionRepository)->listTransactionByWorkflow($workflowId);

        $this->assertSame(0, $result->getCount());
        $this->assertSame([], $result->getTransitions());
    }

    private function makeTransition(string $id, string $name): WorkflowTransition
    {
        return WorkflowTransition::add(
            WorkflowTransitionId::fromString($id),
            WorkflowId::fromString('33333333-3333-4333-8333-333333333333'),
            TransitionName::fromString($name),
            WorkflowStatusId::fromString('44444444-4444-4444-8444-444444444444'),
            WorkflowStatusId::fromString('55555555-5555-4555-8555-555555555555'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
    }

    private function makeService(WorkflowTransitionRepositoryInterface $transitions): WorkflowApiService
    {
        // Interactors are final and cannot be doubled; build real ones with stubbed ports.
        // listTransactionByWorkflow() does not touch them, so their wiring is irrelevant here.
        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $workflowTeams = $this->createStub(WorkflowTeamRepositoryInterface::class);
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock = $this->createStub(ClockInterface::class);
        $validator = $this->createStub(WorkflowValidatorInterface::class);

        return new WorkflowApiService(
            new CreateWorkflowInteractor($repository, $statuses, $transitions, $dispatcher, $clock),
            new UpdateWorkflowInteractor($repository, $dispatcher),
            new AddWorkflowStatusInteractor($repository, $statuses, $dispatcher, $clock),
            new UpdateWorkflowStatusInteractor($repository, $statuses, $dispatcher),
            new AddWorkflowTransitionInteractor($repository, $statuses, $transitions, $dispatcher, $clock),
            new UpdateWorkflowTransitionInteractor($repository, $statuses, $transitions, $dispatcher),
            new AttachWorkflowToTeamInteractor($repository, $workflowTeams, $clock),
            new DetachWorkflowFromTeamInteractor($repository, $workflowTeams),
            $repository,
            $statuses,
            $transitions,
            $workflowTeams,
            new WorkflowDataMapper(),
            $validator,
            $this->createStub(DescriptionServiceInterface::class),
            $this->createStub(TagServiceInterface::class),
            $this->createStub(TeamServiceInterface::class),
        );
    }
}
