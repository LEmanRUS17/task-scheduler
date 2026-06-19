<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Application\ApiService;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\WorkflowFeature\Application\ApiService\WorkflowApiService;
use App\WorkflowFeature\Application\DataMapper\WorkflowDataMapper;
use App\WorkflowFeature\Application\DTORequestValidator\WorkflowValidatorInterface;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Interactor\CreateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class WorkflowApiServiceGetByIdsTest extends TestCase
{
    public function testGetByIdsPreservesIdOrderRegardlessOfRepositoryOrder(): void
    {
        $wf1 = $this->makeWorkflow('11111111-1111-4111-8111-111111111111', 'Bug flow');
        $wf2 = $this->makeWorkflow('22222222-2222-4222-8222-222222222222', 'Release flow');
        $wf3 = $this->makeWorkflow('33333333-3333-4333-8333-333333333333', 'Hotfix flow');

        // Repository returns them in a different (e.g. DB) order.
        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('findByIds')->willReturn([$wf3, $wf1, $wf2]);

        $service = $this->makeService($repository);

        $ids = [$wf1->id()->value(), $wf2->id()->value(), $wf3->id()->value()];
        $results = $service->getByIds($ids);

        $this->assertSame($ids, array_map(static fn($r) => $r->getId(), $results));
        $this->assertSame('Bug flow', $results[0]->getTitle());
    }

    public function testGetByIdsSkipsMissingWorkflows(): void
    {
        $wf1 = $this->makeWorkflow('11111111-1111-4111-8111-111111111111', 'Bug flow');

        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('findByIds')->willReturn([$wf1]);

        $service = $this->makeService($repository);

        $results = $service->getByIds([$wf1->id()->value(), 'missing-id']);

        $this->assertCount(1, $results);
        $this->assertSame($wf1->id()->value(), $results[0]->getId());
    }

    public function testGetByIdsWithEmptyListReturnsEmpty(): void
    {
        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('findByIds')->willReturn([]);

        $this->assertSame([], $this->makeService($repository)->getByIds([]));
    }

    public function testGetPageMapsPaginatedWorkflows(): void
    {
        $wf1 = $this->makeWorkflow('11111111-1111-4111-8111-111111111111', 'Bug flow');
        $wf2 = $this->makeWorkflow('22222222-2222-4222-8222-222222222222', 'Release flow');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findPaginated')
            ->with(10, 20)
            ->willReturn([$wf1, $wf2]);

        $results = $this->makeService($repository)->getPage(10, 20);

        $this->assertSame(
            [$wf1->id()->value(), $wf2->id()->value()],
            array_map(static fn($r) => $r->getId(), $results),
        );
    }

    public function testCountAllDelegatesToRepository(): void
    {
        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('count')->willReturn(42);

        $this->assertSame(42, $this->makeService($repository)->countAll());
    }

    private function makeWorkflow(string $id, string $title): Workflow
    {
        return Workflow::create(
            WorkflowId::fromString($id),
            WorkflowTitle::fromString($title),
            'user-1',
            new \DateTimeImmutable('2024-01-01 00:00:00'),
        );
    }

    private function makeService(WorkflowRepositoryInterface $repository): WorkflowApiService
    {
        // Interactors are final and cannot be doubled; build real ones with stubbed ports.
        // getByIds() does not touch them, so their wiring is irrelevant here.
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock = $this->createStub(ClockInterface::class);

        return new WorkflowApiService(
            new CreateWorkflowInteractor($repository, $dispatcher, $clock),
            new UpdateWorkflowInteractor($repository, $dispatcher),
            new AddWorkflowStatusInteractor($repository, $statuses, $dispatcher, $clock),
            new UpdateWorkflowStatusInteractor($repository, $statuses, $dispatcher),
            new AddWorkflowTransitionInteractor($repository, $statuses, $transitions, $dispatcher, $clock),
            new UpdateWorkflowTransitionInteractor($repository, $statuses, $transitions, $dispatcher),
            $repository,
            $statuses,
            $transitions,
            new WorkflowDataMapper(),
            $this->createStub(WorkflowValidatorInterface::class),
            $this->createStub(DescriptionServiceInterface::class),
        );
    }
}
