<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Domain\Entity;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Event\TeamCreated;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;

final class TeamTest extends TestCase
{
    public function testCreate(): void
    {
        $id = TeamId::generate();
        $title = Title::fromString('test_team_create');
        $createdAt = new \DateTimeImmutable('2024-01-01 13:00:00');

        $team = Team::create($id, $title, $createdAt);

        $this->assertSame($id->value(), $team->id()->value());
        $this->assertSame('test_team_create', $team->title()->value());
        $this->assertSame($createdAt, $team->createdAt());
    }

    public function testCreateDispatchesTeamCreatedEvent(): void
    {
        $team = Team::create(
            TeamId::generate(),
            Title::fromString('test_team_create'),
            new \DateTimeImmutable(),
        );

        $events = $team->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TeamCreated::class, $events[0]);
    }

    public function testTeamCreatedEventContainsCorrectData(): void
    {
        $id = TeamId::generate();
        $title = Title::fromString('test_team_create');

        $team = Team::create($id, $title, new \DateTimeImmutable());

        /** @var TeamCreated $event */
        $event = $team->pullDomainEvents()[0];

        $this->assertSame($id->value(), $event->id->value());
        $this->assertSame('test_team_create', $event->title->value());
    }

    public function testPullDomainEventsClearsQueue(): void
    {
        $team = Team::create(
            TeamId::generate(),
            Title::fromString('test_team_create'),
            new \DateTimeImmutable(),
        );

        $team->pullDomainEvents();

        $this->assertEmpty($team->pullDomainEvents());
    }
}
