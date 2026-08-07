<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Application\Interactor;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Event\TeamGet;
use App\TeamFeature\Domain\Interactor\TeamGetInteractor;
use App\TeamFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use PHPUnit\Framework\TestCase;

final class TeamGetInteractorTest extends TestCase
{
    private TeamId $teamId;

    protected function setUp(): void
    {
        $this->teamId = TeamId::generate();
    }

    private function buildInteractor(
        TeamMemberRepositoryInterface $members,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): TeamGetInteractor {
        return new TeamGetInteractor(
            $members,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
        );
    }

    public function testGetReturnsTrueWhenUserIsMember(): void
    {
        $member = TeamMember::add($this->teamId, 'user-1', TeamMemberRole::MEMBER, new \DateTimeImmutable());

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn($member);

        $result = $this->buildInteractor($members)->get($this->teamId->value(), 'user-1');

        $this->assertTrue($result);
    }

    public function testGetDispatchesTeamGetEvent(): void
    {
        $member = TeamMember::add($this->teamId, 'user-1', TeamMemberRole::MEMBER, new \DateTimeImmutable());

        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn($member);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                fn(TeamGet $event) => $event->teamId === $this->teamId->value(),
            ));

        $this->buildInteractor($members, $dispatcher)->get($this->teamId->value(), 'user-1');
    }

    public function testGetThrowsWhenUserIsNotMember(): void
    {
        $members = $this->createStub(TeamMemberRepositoryInterface::class);
        $members->method('findByTeamAndUser')->willReturn(null);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        $this->buildInteractor($members, $dispatcher)->get($this->teamId->value(), 'user-1');
    }
}
