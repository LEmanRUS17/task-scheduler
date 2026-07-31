<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\EventSubscriber;

use App\NotificationFeature\Infrastructure\Messenger\EventSubscriber\TeamNotificationSubscriber;
use App\NotificationFeature\Infrastructure\Messenger\Message\TeamMemberInvitedMessage;
use App\TeamFeature\Domain\Event\TeamMemberInvited;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TeamNotificationSubscriberTest extends TestCase
{
    public function testOnTeamMemberInvitedDispatchesMessageWithTokenAndEmail(): void
    {
        $teamId = TeamId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $event = new TeamMemberInvited(
            $teamId,
            'Backend',
            'invitation-1',
            'user-1',
            'invitee@example.com',
            'owner-1',
            TeamMemberRole::MEMBER,
            'token-abc',
        );

        $captured = null;
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (TeamMemberInvitedMessage $message) use (&$captured) {
                $captured = $message;

                return new Envelope($message);
            });

        (new TeamNotificationSubscriber($bus))->onTeamMemberInvited($event);

        $this->assertInstanceOf(TeamMemberInvitedMessage::class, $captured);
        $this->assertSame($teamId->value(), $captured->teamId);
        $this->assertSame('Backend', $captured->teamTitle);
        $this->assertSame('invitee@example.com', $captured->invitedEmail);
        $this->assertSame('token-abc', $captured->token);
    }
}
