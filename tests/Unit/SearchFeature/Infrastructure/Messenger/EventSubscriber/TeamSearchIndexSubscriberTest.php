<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\EventSubscriber\TeamSearchIndexSubscriber;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTeamMessage;
use App\TeamFeature\Domain\Event\TeamCreated;
use App\TeamFeature\Domain\Event\TeamMemberAdded;
use App\TeamFeature\Domain\Event\TeamMemberRemoved;
use App\TeamFeature\Domain\Event\TeamUpdated;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TeamSearchIndexSubscriberTest extends TestCase
{
    private function makeBus(?IndexTeamMessage &$captured = null): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (IndexTeamMessage $message) use (&$captured) {
                $captured = $message;
                return new Envelope($message);
            });

        return $bus;
    }

    public function testOnTeamCreatedDispatchesIndexTeamMessageWithTeamId(): void
    {
        $teamId = TeamId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $event = new TeamCreated($teamId, Title::fromString('Backend'));

        $captured = null;
        (new TeamSearchIndexSubscriber($this->makeBus($captured)))->onTeamCreated($event);

        $this->assertInstanceOf(IndexTeamMessage::class, $captured);
        $this->assertSame($teamId->value(), $captured->teamId);
    }

    public function testOnTeamUpdatedDispatchesIndexTeamMessageWithTeamId(): void
    {
        $event = new TeamUpdated('550e8400-e29b-4d4d-a716-446655440000');

        $captured = null;
        (new TeamSearchIndexSubscriber($this->makeBus($captured)))->onTeamUpdated($event);

        $this->assertInstanceOf(IndexTeamMessage::class, $captured);
        $this->assertSame('550e8400-e29b-4d4d-a716-446655440000', $captured->teamId);
    }

    public function testOnTeamMemberAddedDispatchesIndexTeamMessageWithTeamId(): void
    {
        $teamId = TeamId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $event = new TeamMemberAdded($teamId, 'user-1', TeamMemberRole::OWNER);

        $captured = null;
        (new TeamSearchIndexSubscriber($this->makeBus($captured)))->onTeamMemberAdded($event);

        $this->assertInstanceOf(IndexTeamMessage::class, $captured);
        $this->assertSame($teamId->value(), $captured->teamId);
    }

    public function testOnTeamMemberRemovedDispatchesIndexTeamMessageWithTeamId(): void
    {
        $teamId = TeamId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $event = new TeamMemberRemoved($teamId, 'user-1');

        $captured = null;
        (new TeamSearchIndexSubscriber($this->makeBus($captured)))->onTeamMemberRemoved($event);

        $this->assertInstanceOf(IndexTeamMessage::class, $captured);
        $this->assertSame($teamId->value(), $captured->teamId);
    }
}
