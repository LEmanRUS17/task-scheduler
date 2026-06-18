<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\Handler;

use App\SearchFeature\Domain\Port\TeamSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Handler\IndexTeamHandler;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTeamMessage;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use PHPUnit\Framework\TestCase;

final class IndexTeamHandlerTest extends TestCase
{
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->createdAt = new \DateTimeImmutable('2024-01-01 13:00:00');
    }

    private function makeTeam(
        string $id = 'team-uuid',
        string $title = 'Backend',
        string $status = 'active',
    ): TeamDataResponseInterface {
        $team = $this->createStub(TeamDataResponseInterface::class);
        $team->method('getId')->willReturn($id);
        $team->method('getTitle')->willReturn($title);
        $team->method('getStatus')->willReturn($status);
        $team->method('getCreatedAt')->willReturn($this->createdAt);

        return $team;
    }

    private function makeMember(string $userId, string $role = 'member'): TeamMemberDataResponseInterface
    {
        $member = $this->createStub(TeamMemberDataResponseInterface::class);
        $member->method('getUserId')->willReturn($userId);
        $member->method('getRole')->willReturn($role);

        return $member;
    }

    public function testIndexesTeamWithMembersAndOwnerWhenFound(): void
    {
        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getById')->willReturn($this->makeTeam());
        $teamService->method('getMembers')->willReturn([
            $this->makeMember('user-1', 'owner'),
            $this->makeMember('user-2'),
        ]);

        $searchIndex = $this->createMock(TeamSearchIndexInterface::class);
        $searchIndex->expects($this->once())
            ->method('index')
            ->with('team-uuid', 'Backend', 'active', 'user-1', $this->createdAt, ['user-1', 'user-2']);

        (new IndexTeamHandler($teamService, $searchIndex))(new IndexTeamMessage('team-uuid'));
    }

    public function testIndexesTeamWithEmptyOwnerWhenNoOwnerMember(): void
    {
        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getById')->willReturn($this->makeTeam());
        $teamService->method('getMembers')->willReturn([]);

        $searchIndex = $this->createMock(TeamSearchIndexInterface::class);
        $searchIndex->expects($this->once())
            ->method('index')
            ->with('team-uuid', 'Backend', 'active', '', $this->createdAt, []);

        (new IndexTeamHandler($teamService, $searchIndex))(new IndexTeamMessage('team-uuid'));
    }

    public function testDoesNothingWhenTeamNotFound(): void
    {
        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getById')->willReturn(null);

        $searchIndex = $this->createMock(TeamSearchIndexInterface::class);
        $searchIndex->expects($this->never())->method('index');

        (new IndexTeamHandler($teamService, $searchIndex))(new IndexTeamMessage('team-uuid'));
    }
}
