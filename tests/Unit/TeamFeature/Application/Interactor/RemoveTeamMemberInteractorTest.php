<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Interactor\RemoveTeamMemberInteractor;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use PHPUnit\Framework\TestCase;

final class RemoveTeamMemberInteractorTest extends TestCase
{
    private TeamId $teamId;
    private TeamMember $member;

    protected function setUp(): void
    {
        $this->teamId = TeamId::generate();
        $this->member = TeamMember::add($this->teamId, 'user-170426', TeamMemberRole::MEMBER, new \DateTimeImmutable());
    }

    private function buildInteractor(
        TeamMemberRepositoryInterface $members,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): RemoveTeamMemberInteractor {
        return new RemoveTeamMemberInteractor(
            $members,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
        );
    }

    public function testRemoveDeletesMember(): void
    {
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn($this->member);
        $members->expects($this->once())->method('delete')->with($this->member);

        $this->buildInteractor($members)->remove($this->teamId, 'user-170426');
    }

    public function testRemoveThrowsWhenMemberNotFound(): void
    {
        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($members)->remove($this->teamId, 'user-170426');
    }
}
