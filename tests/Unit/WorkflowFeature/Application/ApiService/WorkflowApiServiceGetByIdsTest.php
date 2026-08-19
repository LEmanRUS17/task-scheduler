<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Application\ApiService;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\WorkflowFeature\Application\ApiService\WorkflowApiService;
use App\WorkflowFeature\Application\DataMapper\WorkflowDataMapper;
use App\WorkflowFeature\Application\DTORequest\CreateWorkflowRequestDTO;
use App\WorkflowFeature\Application\DTORequest\CreateWorkflowStatusRequestDTO;
use App\WorkflowFeature\Application\DTORequest\CreateWorkflowTransitionRequestDTO;
use App\WorkflowFeature\Application\DTORequestValidator\WorkflowValidatorInterface;
use App\WorkflowFeature\Domain\Entity\Workflow;
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
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use App\TeamFeatureApi\Service\TeamServiceInterface;
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

    public function testGetPageMapsOwnPaginatedWorkflows(): void
    {
        $wf1 = $this->makeWorkflow('11111111-1111-4111-8111-111111111111', 'Bug flow');
        $wf2 = $this->makeWorkflow('22222222-2222-4222-8222-222222222222', 'Release flow');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->expects($this->once())
            ->method('findByCreatedBy')
            ->with('user-1', 10, 20)
            ->willReturn([$wf1, $wf2]);

        $results = $this->makeService($repository)->getPage(10, 20, 'user-1');

        $this->assertSame(
            [$wf1->id()->value(), $wf2->id()->value()],
            array_map(static fn($r) => $r->getId(), $results),
        );
    }

    public function testCountAllDelegatesToRepository(): void
    {
        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('countByCreatedBy')->willReturn(42);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);

        $this->assertSame(42, $this->makeService($repository)->countAll('user-1'));
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

    private function makeService(
        WorkflowRepositoryInterface $repository,
        ?WorkflowValidatorInterface $validator = null,
        ?TagServiceInterface $tagService = null,
        ?ClockInterface $clock = null,
    ): WorkflowApiService {
        // Interactors are final and cannot be doubled; build real ones with stubbed ports.
        // getByIds() does not touch them, so their wiring is irrelevant here.
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $workflowTeams = $this->createStub(WorkflowTeamRepositoryInterface::class);
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock ??= $this->createStub(ClockInterface::class);
        $validator ??= $this->createStub(WorkflowValidatorInterface::class);
        $tagService ??= $this->createStub(TagServiceInterface::class);

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
            $tagService,
            $this->createStub(TeamServiceInterface::class),
        );
    }

    public function testCreateAssignsEachProvidedTagToTheNewWorkflow(): void
    {
        $repository = $this->createStub(WorkflowRepositoryInterface::class);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));

        $validator = $this->createStub(WorkflowValidatorInterface::class);
        $validator->method('validate')->willReturn([]);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->method('filterExistingTagIds')->willReturn(['tag-1', 'tag-2']);

        $assignedTagIds = [];
        $tagService->expects($this->exactly(2))
            ->method('assign')
            ->willReturnCallback(
                function (
                    string $tagId,
                    string $entityType,
                    string $entityId,
                    string $assignedBy,
                ) use (&$assignedTagIds): void {
                    $this->assertSame(TagServiceInterface::TYPE_WORKFLOW, $entityType);
                    $this->assertSame('user-creator', $assignedBy);
                    $this->assertNotSame('', $entityId);
                    $assignedTagIds[] = $tagId;
                },
            );

        $request = new CreateWorkflowRequestDTO(
            title: 'Tagged flow',
            statuses: [
                new CreateWorkflowStatusRequestDTO('open', isInitial: true),
                new CreateWorkflowStatusRequestDTO('done', isFinal: true),
            ],
            transitions: [
                new CreateWorkflowTransitionRequestDTO('close', 'open', 'done'),
            ],
            tagIds: ['tag-1', 'tag-2'],
        );

        $this->makeService($repository, $validator, $tagService, $clock)->create($request, 'user-creator');

        $this->assertSame(['tag-1', 'tag-2'], $assignedTagIds);
    }

    public function testCreateRejectsUnknownTagIdsAndDoesNotPersistWorkflow(): void
    {
        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->expects($this->never())->method('save');

        $validator = $this->createStub(WorkflowValidatorInterface::class);
        $validator->method('validate')->willReturn([]);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->method('filterExistingTagIds')->willReturn(['tag-1']);
        $tagService->expects($this->never())->method('assign');

        $request = new CreateWorkflowRequestDTO(title: 'Tagged flow', tagIds: ['tag-1', 'missing-tag']);

        try {
            $this->makeService($repository, $validator, $tagService)->create($request, 'user-creator');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('missing-tag', $e->getMessage());
        }
    }
}
