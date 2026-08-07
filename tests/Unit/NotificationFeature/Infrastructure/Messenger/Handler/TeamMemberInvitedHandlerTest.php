<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Handler\TeamMemberInvitedHandler;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\NotificationFeature\Infrastructure\Messenger\Message\TeamMemberInvitedMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TeamMemberInvitedHandlerTest extends TestCase
{
    private function makeMessage(): TeamMemberInvitedMessage
    {
        return new TeamMemberInvitedMessage(
            teamId: 'team-uuid',
            teamTitle: 'Backend',
            invitedEmail: 'invitee@example.com',
            token: 'token-abc',
        );
    }

    public function testSendsEmailWithInvitationToken(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $handler = new TeamMemberInvitedHandler($mailer, $this->makeBusStub());
        $handler($this->makeMessage());
    }

    public function testDispatchesNotificationDispatchMessageWithToken(): void
    {
        $dispatched = null;
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($message) use (&$dispatched) {
                $dispatched = $message;
                return new Envelope($message);
            });

        $handler = new TeamMemberInvitedHandler($this->createStub(MailerInterface::class), $bus);
        $handler($this->makeMessage());

        $this->assertInstanceOf(NotificationDispatchMessage::class, $dispatched);
        $this->assertSame('team.member_invited', $dispatched->event);
        $this->assertInstanceOf(MessageAction::class, $dispatched->action);
        $this->assertSame('email', $dispatched->action->channel);
        $this->assertSame('invitee@example.com', $dispatched->action->recipient);
        $this->assertStringContainsString('token-abc', $dispatched->action->body);
        $this->assertStringContainsString('Backend', $dispatched->action->body);
    }

    private function makeBusStub(): MessageBusInterface
    {
        $stub = $this->createStub(MessageBusInterface::class);
        $stub->method('dispatch')->willReturnCallback(fn($m) => new Envelope($m));
        return $stub;
    }
}
