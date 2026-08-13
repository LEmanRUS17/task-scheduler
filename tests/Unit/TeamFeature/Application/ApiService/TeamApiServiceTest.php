<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\ApiService;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TeamFeature\Application\ApiService\TeamApiService;
use App\TeamFeature\Application\DataMapper\TeamDataMapper;
use App\TeamFeature\Application\DTORequest\TeamCreateRequestDTO;
use App\TeamFeature\Application\DTORequest\TeamInviteMemberRequestDTO;
use App\TeamFeature\Application\DTORequestValidator\TeamValidatorInterface;
use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Interactor\AcceptTeamInvitationInteractor;
use App\TeamFeature\Domain\Interactor\AddTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\InviteTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\RemoveTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\TeamCreateInteractor;
use App\TeamFeature\Domain\Interactor\TeamDeleteInteractor;
use App\TeamFeature\Domain\Interactor\TeamGetInteractor;
use App\TeamFeature\Domain\Interactor\TeamUpdateInteractor;
use App\TeamFeature\Domain\Port\ClockInterface;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamInvitationRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeature\Domain\ValueObject\Title;
use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use PHPUnit\Framework\TestCase;

final class TeamApiServiceTest extends TestCase
{
    public function testGetPagePassesUserLimitAndOffsetToRepository(): void
    {
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->expects($this->once())
            ->method('findPaginatedByMemberUserId')
            ->with('user-7', 20, 40)
            ->willReturn([]);

        $this->assertSame([], $this->makeService($teams)->getPage('user-7', 20, 40));
    }

    public function testGetPageMapsTeams(): void
    {
        $team = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');

        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findPaginatedByMemberUserId')->willReturn([$team]);

        $result = $this->makeService($teams)->getPage('user-1', 10, 0);

        $this->assertCount(1, $result);
        $this->assertSame($team->id()->value(), $result[0]->getId());
        $this->assertSame('Backend', $result[0]->getTitle());
    }

    public function testCountAllDelegatesToRepository(): void
    {
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->expects($this->once())
            ->method('countByMemberUserId')
            ->with('user-7')
            ->willReturn(42);

        $this->assertSame(42, $this->makeService($teams)->countAll('user-7'));
    }

    public function testGetByIdsPreservesIdOrderRegardlessOfRepositoryOrder(): void
    {
        $t1 = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');
        $t2 = $this->makeTeam('22222222-2222-4222-8222-222222222222', 'Frontend');
        $t3 = $this->makeTeam('33333333-3333-4333-8333-333333333333', 'Platform');

        // Repository returns them in a different (e.g. DB) order.
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findByIds')->willReturn([$t3, $t1, $t2]);

        $ids = [$t1->id()->value(), $t2->id()->value(), $t3->id()->value()];
        $result = $this->makeService($teams)->getByIds($ids);

        $this->assertSame($ids, array_map(static fn($r) => $r->getId(), $result));
        $this->assertSame('Backend', $result[0]->getTitle());
    }

    public function testGetByIdsSkipsMissingTeams(): void
    {
        $t1 = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');

        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findByIds')->willReturn([$t1]);

        $result = $this->makeService($teams)->getByIds([$t1->id()->value(), 'missing-id']);

        $this->assertCount(1, $result);
        $this->assertSame($t1->id()->value(), $result[0]->getId());
    }

    public function testGetByIdsWithEmptyListReturnsEmpty(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findByIds')->willReturn([]);

        $this->assertSame([], $this->makeService($teams)->getByIds([]));
    }

    public function testGetOwnersQueriesRepositoryByOwnerRole(): void
    {
        $team = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');
        $owner = TeamMember::add(
            $team->id(),
            'owner-1',
            TeamMemberRole::OWNER,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $teams = $this->createStub(TeamRepositoryInterface::class);

        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->expects($this->once())
            ->method('findByTeamIdAndRole')
            ->with($team->id(), TeamMemberRole::OWNER)
            ->willReturn([$owner]);

        $result = $this->makeService($teams, members: $members)->getOwners($team->id()->value());

        $this->assertSame(['owner-1'], $result);
    }

    private function makeTeam(string $id, string $title): Team
    {
        return Team::create(
            TeamId::fromString($id),
            Title::fromString($title),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
    }

    private function makeService(
        TeamRepositoryInterface $teams,
        ?TeamValidatorInterface $validator = null,
        ?TagServiceInterface $tagService = null,
        ?ClockInterface $clock = null,
        ?UserServiceInterface $userService = null,
        ?TeamMemberRepositoryInterface $members = null,
        ?TeamInvitationRepositoryInterface $invitations = null,
        ?ProfileServiceInterface $profiles = null,
        ?SearchServiceInterface $searchService = null,
    ): TeamApiService {
        // Interactors are final and cannot be doubled; build real ones with stubbed ports.
        // The paginated/getByIds reads do not touch them, so their wiring is irrelevant here.
        $members ??= $this->createStub(TeamMemberRepositoryInterface::class);
        $invitations ??= $this->createStub(TeamInvitationRepositoryInterface::class);
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock ??= $this->createStub(ClockInterface::class);
        $validator ??= $this->createStub(TeamValidatorInterface::class);
        $tagService ??= $this->createStub(TagServiceInterface::class);
        $userService ??= $this->createStub(UserServiceInterface::class);
        $profiles ??= $this->createStub(ProfileServiceInterface::class);
        $searchService ??= $this->createStub(SearchServiceInterface::class);

        return new TeamApiService(
            new TeamCreateInteractor($teams, $members, $dispatcher, $clock),
            new TeamUpdateInteractor($teams, $dispatcher),
            new TeamGetInteractor($members, $dispatcher),
            new TeamDeleteInteractor($teams, $members, $dispatcher),
            new AddTeamMemberInteractor($teams, $members, $dispatcher, $clock),
            new RemoveTeamMemberInteractor($members, $dispatcher),
            new InviteTeamMemberInteractor($teams, $members, $invitations, $dispatcher, $clock),
            new AcceptTeamInvitationInteractor($invitations, $members, $dispatcher, $clock),
            $teams,
            $members,
            new TeamDataMapper(),
            $validator,
            $this->createStub(DescriptionServiceInterface::class),
            $profiles,
            $tagService,
            $userService,
            $searchService,
        );
    }

    // --- inviteMember ---

    public function testInviteMemberResolvesUserByUserId(): void
    {
        $team = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($team);

        $invitedUser = $this->createStub(UserDataResponseInterface::class);
        $invitedUser->method('getId')->willReturn('invitee-uuid');
        $invitedUser->method('getEmail')->willReturn('invitee@example.com');

        $userService = $this->createMock(UserServiceInterface::class);
        $userService->expects($this->once())->method('findById')->with('invitee-uuid')->willReturn($invitedUser);
        $userService->expects($this->never())->method('findByEmail');

        $request = new TeamInviteMemberRequestDTO(userId: 'invitee-uuid');
        $invitation = $this->makeService($teams, userService: $userService)
            ->inviteMember($team->id()->value(), $request, 'owner-1');

        $this->assertSame('invitee-uuid', $invitation->getInvitedUserId());
        $this->assertSame('owner-1', $invitation->getInvitedByUserId());
    }

    public function testInviteMemberResolvesUserByEmail(): void
    {
        $team = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($team);

        $invitedUser = $this->createStub(UserDataResponseInterface::class);
        $invitedUser->method('getId')->willReturn('invitee-uuid');
        $invitedUser->method('getEmail')->willReturn('invitee@example.com');

        $userService = $this->createMock(UserServiceInterface::class);
        $userService->expects($this->once())
            ->method('findByEmail')
            ->with('invitee@example.com')
            ->willReturn($invitedUser);
        $userService->expects($this->never())->method('findById');

        $request = new TeamInviteMemberRequestDTO(email: 'invitee@example.com');
        $invitation = $this->makeService($teams, userService: $userService)
            ->inviteMember($team->id()->value(), $request, 'owner-1');

        $this->assertSame('invitee-uuid', $invitation->getInvitedUserId());
    }

    public function testInviteMemberThrowsWhenNeitherUserIdNorEmailProvided(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);

        $this->expectException(\InvalidArgumentException::class);

        $this->makeService($teams)->inviteMember('team-1', new TeamInviteMemberRequestDTO(), 'owner-1');
    }

    public function testInviteMemberThrowsWhenBothUserIdAndEmailProvided(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);

        $this->expectException(\InvalidArgumentException::class);

        $request = new TeamInviteMemberRequestDTO(userId: 'user-1', email: 'invitee@example.com');
        $this->makeService($teams)->inviteMember('team-1', $request, 'owner-1');
    }

    public function testInviteMemberThrowsWhenUserNotFound(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findByEmail')->willReturn(null);

        $this->expectException(\DomainException::class);

        $request = new TeamInviteMemberRequestDTO(email: 'unknown@example.com');
        $this->makeService($teams, userService: $userService)->inviteMember('team-1', $request, 'owner-1');
    }

    // --- create with tags ---

    public function testCreateAssignsEachProvidedTagToTheNewTeam(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));

        $validator = $this->createStub(TeamValidatorInterface::class);
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
                    $this->assertSame(TagServiceInterface::TYPE_TEAM, $entityType);
                    $this->assertSame('user-creator', $assignedBy);
                    $this->assertNotSame('', $entityId);
                    $assignedTagIds[] = $tagId;
                },
            );

        $request = new TeamCreateRequestDTO(title: 'Tagged team', tagIds: ['tag-1', 'tag-2']);

        $this->makeService($teams, $validator, $tagService, $clock)->create($request, 'user-creator');

        $this->assertSame(['tag-1', 'tag-2'], $assignedTagIds);
    }

    public function testCreateRejectsUnknownTagIdsAndDoesNotPersistTeam(): void
    {
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->expects($this->never())->method('save');

        $validator = $this->createStub(TeamValidatorInterface::class);
        $validator->method('validate')->willReturn([]);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->method('filterExistingTagIds')->willReturn(['tag-1']);
        $tagService->expects($this->never())->method('assign');

        $request = new TeamCreateRequestDTO(title: 'Tagged team', tagIds: ['tag-1', 'missing-tag']);

        try {
            $this->makeService($teams, $validator, $tagService)->create($request, 'user-creator');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('missing-tag', $e->getMessage());
        }
    }

    // --- searchMembers ---

    public function testSearchMembersThrowsWhenTeamNotFound(): void
    {
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->makeService($teams)->searchMembers('99999999-9999-4999-8999-999999999999', 'user-1');
    }

    public function testSearchMembersPlacesCurrentUserFirst(): void
    {
        $team = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($team);

        $memberOther = TeamMember::add(
            $team->id(),
            'user-other',
            TeamMemberRole::MEMBER,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
        $memberCurrent = TeamMember::add(
            $team->id(),
            'user-current',
            TeamMemberRole::MEMBER,
            new \DateTimeImmutable('2026-01-02 00:00:00'),
        );

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamId')->willReturn([$memberOther, $memberCurrent]);

        $profiles = $this->createStub(ProfileServiceInterface::class);
        $profiles->method('getByUserId')->willReturnCallback(
            fn(string $userId) => $this->makeProfile($userId),
        );

        $result = $this->makeService($teams, members: $members, profiles: $profiles)
            ->searchMembers($team->id()->value(), 'user-current');

        $this->assertCount(2, $result);
        $this->assertSame('user-current', $result[0]->getUserId());
        $this->assertSame('user-other', $result[1]->getUserId());
    }

    public function testSearchMembersFiltersByIdsReturnedFromManticore(): void
    {
        $team = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($team);

        $matching = TeamMember::add(
            $team->id(),
            'user-ivanov',
            TeamMemberRole::MEMBER,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
        $notMatching = TeamMember::add(
            $team->id(),
            'user-petrov',
            TeamMemberRole::MEMBER,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamId')->willReturn([$matching, $notMatching]);

        $profiles = $this->createStub(ProfileServiceInterface::class);
        $profiles->method('getByUserId')->willReturnMap([
            ['user-ivanov', $this->makeProfile('user-ivanov', 'ivan.k', 'Ivan', 'Ivanov')],
            ['user-petrov', $this->makeProfile('user-petrov', 'petya', 'Petr', 'Petrov')],
        ]);

        $searchService = $this->createMock(SearchServiceInterface::class);
        $searchService->expects($this->once())
            ->method('searchTeamUsers')
            ->with($team->id()->value(), 'ivanov', 500)
            ->willReturn(['ids' => ['user-ivanov'], 'total' => 1]);

        $result = $this->makeService($teams, members: $members, profiles: $profiles, searchService: $searchService)
            ->searchMembers($team->id()->value(), 'someone-else', 'ivanov');

        $this->assertCount(1, $result);
        $this->assertSame('user-ivanov', $result[0]->getUserId());
    }

    public function testSearchMembersWithoutNameDoesNotCallManticore(): void
    {
        $team = $this->makeTeam('11111111-1111-4111-8111-111111111111', 'Backend');
        $teams = $this->createStub(TeamRepositoryInterface::class);
        $teams->method('findById')->willReturn($team);

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamId')->willReturn([]);

        $searchService = $this->createMock(SearchServiceInterface::class);
        $searchService->expects($this->never())->method('searchTeamUsers');

        $this->makeService($teams, members: $members, searchService: $searchService)
            ->searchMembers($team->id()->value(), 'user-1');
    }

    private function makeProfile(
        string $userId,
        string $username = 'nick',
        string $firstname = 'First',
        string $lastname = 'Last',
    ): ProfileDataResponseInterface {
        $profile = $this->createStub(ProfileDataResponseInterface::class);
        $profile->method('getUserId')->willReturn($userId);
        $profile->method('getUsername')->willReturn($username);
        $profile->method('getFirstname')->willReturn($firstname);
        $profile->method('getLastname')->willReturn($lastname);
        $profile->method('getMidlname')->willReturn(null);
        $profile->method('getAvatar')->willReturn(null);

        return $profile;
    }
}
