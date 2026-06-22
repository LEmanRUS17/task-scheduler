<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Handler\UserRegisteredHandler;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\NotificationFeature\Infrastructure\Messenger\Message\UserRegisteredMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserRegisteredHandlerTest extends TestCase
{
    private function makeMessage(): UserRegisteredMessage
    {
        return new UserRegisteredMessage(
            userId: 'user-uuid',
            email: 'newcomer@example.com',
            confirmationCode: '654321',
        );
    }

    public function testSendsEmailWithConfirmationCode(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $handler = new UserRegisteredHandler($mailer, $this->makeBusStub());
        $handler($this->makeMessage());
    }

    public function testDispatchesNotificationDispatchMessageWithCode(): void
    {
        $dispatched = null;
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($message) use (&$dispatched) {
                $dispatched = $message;
                return new Envelope($message);
            });

        $handler = new UserRegisteredHandler($this->createStub(MailerInterface::class), $bus);
        $handler($this->makeMessage());

        $this->assertInstanceOf(NotificationDispatchMessage::class, $dispatched);
        $this->assertSame('user.registered', $dispatched->event);
        $this->assertInstanceOf(MessageAction::class, $dispatched->action);
        $this->assertSame('email', $dispatched->action->channel);
        $this->assertSame('newcomer@example.com', $dispatched->action->recipient);
        $this->assertStringContainsString('654321', $dispatched->action->body);
    }

    private function makeBusStub(): MessageBusInterface
    {
        $stub = $this->createStub(MessageBusInterface::class);
        $stub->method('dispatch')->willReturnCallback(fn($m) => new Envelope($m));
        return $stub;
    }
}
