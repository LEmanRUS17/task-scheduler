<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Application\ApiService;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\WorkflowFeature\Application\ApiService\WorkflowApiService;
use App\WorkflowFeature\Application\DataMapper\WorkflowDataMapper;
use App\WorkflowFeature\Application\DTORequestValidator\WorkflowValidatorInterface;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowTeam;
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
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use PHPUnit\Framework\TestCase;

final class WorkflowApiServiceDefaultWorkflowTest extends TestCase
{
    public function testCreateDefaultForUserPersistsWorkflowWithOpenAndClosedStatuses(): void
    {
        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->expects($this->once())->method('save');

        $statuses = $this->createMock(WorkflowStatusRepositoryInterface::class);
        $saved = [];
        $statuses->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($status) use (&$saved): void {
                $saved[$status->label()->value()] = $status;
            });

        $response = $this->makeService($repository, $statuses)->createDefaultForUser('user-1');

        $this->assertTrue($response->isDefault());
        $this->assertSame('user-1', $response->getCreatedBy());
        $this->assertTrue($saved['открыт']->isInitial());
        $this->assertFalse($saved['открыт']->isFinal());
        $this->assertTrue($saved['закрыт']->isFinal());
        $this->assertFalse($saved['закрыт']->isInitial());
    }

    public function testCreateDefaultForUserIsIdempotentWhenDefaultAlreadyExists(): void
    {
        $existing = Workflow::create(
            WorkflowId::generate(),
            WorkflowTitle::fromString('Базовый'),
            'user-1',
            new \DateTimeImmutable('2024-01-01 00:00:00'),
            true,
        );

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->expects($this->once())->method('findDefaultByCreatedBy')->with('user-1')->willReturn($existing);
        $repository->expects($this->never())->method('save');

        $response = $this->makeService($repository)->createDefaultForUser('user-1');

        $this->assertSame($existing->id()->value(), $response->getId());
        $this->assertTrue($response->isDefault());
    }

    public function testGetDefaultForUserReturnsNullWhenNoneExists(): void
    {
        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);

        $this->assertNull($this->makeService($repository)->getDefaultForUser('user-1'));
    }

    public function testGetDefaultForUserReturnsWorkflowMarkedAsDefault(): void
    {
        $workflow = Workflow::create(
            WorkflowId::generate(),
            WorkflowTitle::fromString('Базовый'),
            'user-1',
            new \DateTimeImmutable('2024-01-01 00:00:00'),
            true,
        );

        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn($workflow);

        $response = $this->makeService($repository)->getDefaultForUser('user-1');

        $this->assertNotNull($response);
        $this->assertTrue($response->isDefault());
    }

    public function testGetPagePinsOwnDefaultWorkflowFirstOnFirstPage(): void
    {
        $default = $this->makeWorkflow('Базовый', true);
        $other1 = $this->makeWorkflow('Bug flow');
        $other2 = $this->makeWorkflow('Release flow');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findDefaultByCreatedBy')
            ->with('user-1')
            ->willReturn($default);
        $repository->expects($this->once())
            ->method('findByCreatedBy')
            ->with('user-1', 9, 0)
            ->willReturn([$other1, $other2]);

        $results = $this->makeService($repository)->getPage(10, 0, 'user-1');

        $this->assertSame(
            ['Базовый', 'Bug flow', 'Release flow'],
            array_map(static fn($r) => $r->getTitle(), $results),
        );
        $this->assertTrue($results[0]->isDefault());
    }

    public function testGetPageExcludesOwnDefaultWorkflowWhenIncludeDefaultIsFalse(): void
    {
        $other = $this->makeWorkflow('Bug flow');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->expects($this->never())->method('findDefaultByCreatedBy');
        $repository->expects($this->once())
            ->method('findByCreatedBy')
            ->with('user-1', 10, 0)
            ->willReturn([$other]);

        $results = $this->makeService($repository)->getPage(10, 0, 'user-1', includeDefault: false);

        $this->assertSame(['Bug flow'], array_map(static fn($r) => $r->getTitle(), $results));
    }

    public function testGetPageOnFirstPageWithoutOwnDefaultDoesNotPin(): void
    {
        $other = $this->makeWorkflow('Bug flow');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->expects($this->once())
            ->method('findByCreatedBy')
            ->with('user-1', 10, 0)
            ->willReturn([$other]);

        $results = $this->makeService($repository)->getPage(10, 0, 'user-1');

        $this->assertSame(['Bug flow'], array_map(static fn($r) => $r->getTitle(), $results));
    }

    public function testGetPageOnLaterPageShiftsOffsetToAccountForPinnedDefault(): void
    {
        $default = $this->makeWorkflow('Базовый', true);
        $other = $this->makeWorkflow('Some flow');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn($default);
        $repository->expects($this->once())
            ->method('findByCreatedBy')
            ->with('user-1', 10, 9)
            ->willReturn([$other]);

        $results = $this->makeService($repository)->getPage(10, 10, 'user-1');

        $this->assertSame(['Some flow'], array_map(static fn($r) => $r->getTitle(), $results));
    }

    public function testGetPageNeverReturnsWorkflowsOwnedByOtherUsersWhenNoTeamGiven(): void
    {
        $mine = $this->makeWorkflow('Mine', createdBy: 'user-1');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->expects($this->once())
            ->method('findByCreatedBy')
            ->with('user-1', 10, 0)
            ->willReturn([$mine]);

        $results = $this->makeService($repository)->getPage(10, 0, 'user-1');

        $this->assertSame(['Mine'], array_map(static fn($r) => $r->getTitle(), $results));
    }

    public function testGetPageAppendsTeamAttachedWorkflowsNotOwnedByCallerAndTagsTeamTitle(): void
    {
        $mine = $this->makeWorkflow('Mine', createdBy: 'user-1');
        $teammates = $this->makeWorkflow('Teammate flow', createdBy: 'user-2');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->expects($this->once())
            ->method('findByCreatedBy')
            ->with('user-1', 10, 0)
            ->willReturn([$mine]);
        $repository->expects($this->once())
            ->method('findByIds')
            ->with([$teammates->id()->value()])
            ->willReturn([$teammates]);

        $workflowTeams = $this->createStub(WorkflowTeamRepositoryInterface::class);
        $workflowTeams->method('findByTeamId')->willReturn([
            WorkflowTeam::attach($teammates->id(), 'team-1', new \DateTimeImmutable('2026-01-01')),
        ]);

        $team = $this->createStub(TeamDataResponseInterface::class);
        $team->method('getTitle')->willReturn('Engineering');
        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getById')->willReturn($team);

        $results = $this->makeService($repository, workflowTeams: $workflowTeams, teamService: $teamService)
            ->getPage(10, 0, 'user-1', 'team-1');

        $this->assertSame(
            ['Mine', 'Teammate flow'],
            array_map(static fn($r) => $r->getTitle(), $results),
        );
        $this->assertNull($results[0]->getTeamTitle());
        $this->assertSame('Engineering', $results[1]->getTeamTitle());
    }

    public function testGetPageDoesNotDuplicateWorkflowAlreadyOwnedByCallerWhenAlsoAttachedToTeam(): void
    {
        $mine = $this->makeWorkflow('Mine', createdBy: 'user-1');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->method('findByCreatedBy')->willReturn([$mine]);
        $repository->expects($this->never())->method('findByIds');

        $workflowTeams = $this->createStub(WorkflowTeamRepositoryInterface::class);
        $workflowTeams->method('findByTeamId')->willReturn([
            WorkflowTeam::attach($mine->id(), 'team-1', new \DateTimeImmutable('2026-01-01')),
        ]);

        $team = $this->createStub(TeamDataResponseInterface::class);
        $team->method('getTitle')->willReturn('Engineering');
        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getById')->willReturn($team);

        $results = $this->makeService($repository, workflowTeams: $workflowTeams, teamService: $teamService)
            ->getPage(10, 0, 'user-1', 'team-1');

        $this->assertCount(1, $results);
        $this->assertSame('Engineering', $results[0]->getTeamTitle());
    }

    public function testGetPageTagsInTeamForWorkflowsAttachedToTheGivenInTeamId(): void
    {
        $attached = $this->makeWorkflow('Attached', createdBy: 'user-1');
        $notAttached = $this->makeWorkflow('Not attached', createdBy: 'user-1');

        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->method('findByCreatedBy')->willReturn([$attached, $notAttached]);

        $workflowTeams = $this->createStub(WorkflowTeamRepositoryInterface::class);
        $workflowTeams->method('findByTeamId')->willReturn([
            WorkflowTeam::attach($attached->id(), 'team-1', new \DateTimeImmutable('2026-01-01')),
        ]);

        $results = $this->makeService($repository, workflowTeams: $workflowTeams)
            ->getPage(10, 0, 'user-1', inTeamId: 'team-1');

        $this->assertTrue($results[0]->isInTeam());
        $this->assertFalse($results[1]->isInTeam());
    }

    public function testGetPageLeavesInTeamFalseWhenInTeamIdIsNotGiven(): void
    {
        $workflow = $this->makeWorkflow('Mine', createdBy: 'user-1');

        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->method('findByCreatedBy')->willReturn([$workflow]);

        $workflowTeams = $this->createMock(WorkflowTeamRepositoryInterface::class);
        $workflowTeams->expects($this->never())->method('findByTeamId');

        $results = $this->makeService($repository, workflowTeams: $workflowTeams)->getPage(10, 0, 'user-1');

        $this->assertFalse($results[0]->isInTeam());
    }

    public function testGetPageDoesNotAppendTeamWorkflowsOnLaterPages(): void
    {
        $mine = $this->makeWorkflow('Mine', createdBy: 'user-1');

        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->method('findByCreatedBy')->willReturn([$mine]);
        $repository->expects($this->never())->method('findByIds');

        $workflowTeams = $this->createMock(WorkflowTeamRepositoryInterface::class);
        $workflowTeams->expects($this->once())->method('findByTeamId')->willReturn([]);

        $results = $this->makeService($repository, workflowTeams: $workflowTeams)
            ->getPage(10, 10, 'user-1', 'team-1');

        $this->assertSame(['Mine'], array_map(static fn($r) => $r->getTitle(), $results));
    }

    public function testCountAllIncludesOwnDefaultWorkflow(): void
    {
        $default = $this->makeWorkflow('Базовый', true);

        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('countByCreatedBy')->willReturn(5);
        $repository->method('findDefaultByCreatedBy')->willReturn($default);

        $this->assertSame(6, $this->makeService($repository)->countAll('user-1'));
    }

    public function testCountAllExcludesDefaultWhenUserHasNone(): void
    {
        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('countByCreatedBy')->willReturn(5);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);

        $this->assertSame(5, $this->makeService($repository)->countAll('user-1'));
    }

    public function testCountAllExcludesDefaultWhenIncludeDefaultIsFalse(): void
    {
        $default = $this->makeWorkflow('Базовый', true);

        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('countByCreatedBy')->willReturn(5);
        $repository->method('findDefaultByCreatedBy')->willReturn($default);

        $this->assertSame(5, $this->makeService($repository)->countAll('user-1', includeDefault: false));
    }

    public function testCountAllAddsTeamAttachedWorkflowsNotOwnedByCaller(): void
    {
        $teammates = $this->makeWorkflow('Teammate flow', createdBy: 'user-2');

        $repository = $this->createStub(WorkflowRepositoryInterface::class);
        $repository->method('countByCreatedBy')->willReturn(5);
        $repository->method('findDefaultByCreatedBy')->willReturn(null);
        $repository->method('findByIds')->willReturn([$teammates]);

        $workflowTeams = $this->createStub(WorkflowTeamRepositoryInterface::class);
        $workflowTeams->method('findByTeamId')->willReturn([
            WorkflowTeam::attach($teammates->id(), 'team-1', new \DateTimeImmutable('2026-01-01')),
        ]);

        $this->assertSame(
            6,
            $this->makeService($repository, workflowTeams: $workflowTeams)->countAll('user-1', 'team-1'),
        );
    }

    private function makeWorkflow(string $title, bool $isDefault = false, string $createdBy = 'user-1'): Workflow
    {
        return Workflow::create(
            WorkflowId::generate(),
            WorkflowTitle::fromString($title),
            $createdBy,
            new \DateTimeImmutable('2024-01-01 00:00:00'),
            $isDefault,
        );
    }

    private function makeService(
        WorkflowRepositoryInterface $repository,
        ?WorkflowStatusRepositoryInterface $statuses = null,
        ?WorkflowTeamRepositoryInterface $workflowTeams = null,
        ?TeamServiceInterface $teamService = null,
    ): WorkflowApiService {
        $statuses ??= $this->createStub(WorkflowStatusRepositoryInterface::class);
        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $workflowTeams ??= $this->createStub(WorkflowTeamRepositoryInterface::class);
        $teamService ??= $this->createStub(TeamServiceInterface::class);
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));

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
            $this->createStub(WorkflowValidatorInterface::class),
            $this->createStub(DescriptionServiceInterface::class),
            $this->createStub(TagServiceInterface::class),
            $teamService,
        );
    }
}
