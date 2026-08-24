<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\ProfileFeature\Domain\Event\ProfileCreated;
use App\ProfileFeature\Domain\Event\ProfileUpdated;
use App\SearchFeature\Infrastructure\Messenger\EventSubscriber\UserSearchIndexSubscriber;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexUserMessage;
use App\TeamFeature\Domain\Event\TeamMemberAdded;
use App\TeamFeature\Domain\Event\TeamMemberRemoved;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserSearchIndexSubscriberTest extends TestCase
{
    private function makeBus(?IndexUserMessage &$captured = null): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (IndexUserMessage $message) use (&$captured) {
                $captured = $message;
                return new Envelope($message);
            });

        return $bus;
    }

    public function testOnProfileCreatedDispatchesIndexUserMessageWithUserId(): void
    {
        $event = new ProfileCreated('user-1');

        $captured = null;
        (new UserSearchIndexSubscriber($this->makeBus($captured)))->onProfileCreated($event);

        $this->assertInstanceOf(IndexUserMessage::class, $captured);
        $this->assertSame('user-1', $captured->userId);
    }

    public function testOnProfileUpdatedDispatchesIndexUserMessageWithUserId(): void
    {
        $event = new ProfileUpdated('user-1');

        $captured = null;
        (new UserSearchIndexSubscriber($this->makeBus($captured)))->onProfileUpdated($event);

        $this->assertInstanceOf(IndexUserMessage::class, $captured);
        $this->assertSame('user-1', $captured->userId);
    }

    public function testOnTeamMemberAddedDispatchesIndexUserMessageWithUserId(): void
    {
        $teamId = TeamId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $event = new TeamMemberAdded($teamId, 'user-1', TeamMemberRole::OWNER);

        $captured = null;
        (new UserSearchIndexSubscriber($this->makeBus($captured)))->onTeamMemberAdded($event);

        $this->assertInstanceOf(IndexUserMessage::class, $captured);
        $this->assertSame('user-1', $captured->userId);
    }

    public function testOnTeamMemberRemovedDispatchesIndexUserMessageWithUserId(): void
    {
        $teamId = TeamId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $event = new TeamMemberRemoved($teamId, 'user-1');

        $captured = null;
        (new UserSearchIndexSubscriber($this->makeBus($captured)))->onTeamMemberRemoved($event);

        $this->assertInstanceOf(IndexUserMessage::class, $captured);
        $this->assertSame('user-1', $captured->userId);
    }
}
