<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Domain\Entity;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Event\TeamMemberAdded;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use PHPUnit\Framework\TestCase;

final class TeamMemberTest extends TestCase
{
    public function testAdd(): void
    {
        $teamId = TeamId::generate();
        $userId = 'user-170426';
        $role = TeamMemberRole::MEMBER;
        $joinedAt = new \DateTimeImmutable('2024-01-01 13:00:00');

        $member = TeamMember::add($teamId, $userId, $role, $joinedAt);

        $this->assertSame($teamId->value(), $member->teamId()->value());
        $this->assertSame($userId, $member->userId());
        $this->assertSame($role, $member->role());
        $this->assertSame($joinedAt, $member->joinedAt());
    }

    public function testAddDispatchesTeamMemberAddedEvent(): void
    {
        $member = TeamMember::add(
            TeamId::generate(),
            'user-170426',
            TeamMemberRole::MEMBER,
            new \DateTimeImmutable(),
        );

        $events = $member->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TeamMemberAdded::class, $events[0]);
    }

    public function testTeamMemberAddedEventContainsCorrectData(): void
    {
        $teamId = TeamId::generate();
        $userId = 'user-170426';
        $role = TeamMemberRole::OWNER;

        $member = TeamMember::add($teamId, $userId, $role, new \DateTimeImmutable());

        /** @var TeamMemberAdded $event */
        $event = $member->pullDomainEvents()[0];

        $this->assertSame($teamId->value(), $event->teamId->value());
        $this->assertSame($userId, $event->userId);
        $this->assertSame($role, $event->role);
    }

    public function testPullDomainEventsClearsQueue(): void
    {
        $member = TeamMember::add(
            TeamId::generate(),
            'user-170426',
            TeamMemberRole::MEMBER,
            new \DateTimeImmutable(),
        );

        $member->pullDomainEvents();

        $this->assertEmpty($member->pullDomainEvents());
    }
}
