<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Handler\TaskCreatedHandler;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskCreatedMessage;
use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TaskCreatedHandlerTest extends TestCase
{
    private function makeMessage(): TaskCreatedMessage
    {
        return new TaskCreatedMessage(
            taskId: 'task-uuid',
            title: 'Fix login bug',
            createdBy: 'user-uuid',
        );
    }

    private function makeUser(string $email = 'creator@example.com'): UserDataResponseInterface
    {
        $user = $this->createStub(UserDataResponseInterface::class);
        $user->method('getId')->willReturn('user-uuid');
        $user->method('getEmail')->willReturn($email);
        return $user;
    }

    private function makeBusStub(): MessageBusInterface
    {
        $stub = $this->createStub(MessageBusInterface::class);
        $stub->method('dispatch')->willReturnCallback(fn($m) => new Envelope($m));
        return $stub;
    }

    private function makeHandler(
        ?UserServiceInterface $userService = null,
        ?MailerInterface $mailer = null,
        ?MessageBusInterface $bus = null,
    ): TaskCreatedHandler {
        return new TaskCreatedHandler(
            $userService ?? $this->createStub(UserServiceInterface::class),
            $mailer ?? $this->createStub(MailerInterface::class),
            $bus ?? $this->makeBusStub(),
        );
    }

    public function testSendsEmailToTaskCreator(): void
    {
        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser());

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        ($this->makeHandler(userService: $userService, mailer: $mailer))($this->makeMessage());
    }

    public function testDispatchesNotificationDispatchMessage(): void
    {
        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser('creator@example.com'));

        $bus = $this->createMock(MessageBusInterface::class);
        $dispatched = null;
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($message) use (&$dispatched) {
                $dispatched = $message;
                return new Envelope($message);
            });

        ($this->makeHandler(userService: $userService, bus: $bus))($this->makeMessage());

        $this->assertInstanceOf(NotificationDispatchMessage::class, $dispatched);
        $this->assertSame('task.created', $dispatched->event);
        $this->assertInstanceOf(MessageAction::class, $dispatched->action);
        $this->assertSame('email', $dispatched->action->channel);
        $this->assertSame('creator@example.com', $dispatched->action->recipient);
        $this->assertStringContainsString('Fix login bug', $dispatched->action->subject);
        $this->assertStringContainsString('Fix login bug', $dispatched->action->body);
    }

    public function testDoesNothingWhenUserNotFound(): void
    {
        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        ($this->makeHandler(userService: $userService, mailer: $mailer, bus: $bus))($this->makeMessage());
    }
}
