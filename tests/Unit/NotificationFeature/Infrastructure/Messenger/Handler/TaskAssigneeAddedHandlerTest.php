<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Handler\TaskAssigneeAddedHandler;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskAssigneeAddedMessage;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TaskAssigneeAddedHandlerTest extends TestCase
{
    private function makeMessage(): TaskAssigneeAddedMessage
    {
        return new TaskAssigneeAddedMessage(taskId: 'task-uuid', userId: 'user-uuid');
    }

    private function makeTask(string $title = 'Deploy to production'): TaskDataResponseInterface
    {
        $task = $this->createStub(TaskDataResponseInterface::class);
        $task->method('getTitle')->willReturn($title);
        return $task;
    }

    private function makeUser(string $email = 'assignee@example.com'): UserDataResponseInterface
    {
        $user = $this->createStub(UserDataResponseInterface::class);
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
        ?TaskServiceInterface $taskService = null,
        ?UserServiceInterface $userService = null,
        ?MailerInterface $mailer = null,
        ?MessageBusInterface $bus = null,
    ): TaskAssigneeAddedHandler {
        return new TaskAssigneeAddedHandler(
            $taskService ?? $this->createStub(TaskServiceInterface::class),
            $userService ?? $this->createStub(UserServiceInterface::class),
            $mailer ?? $this->createStub(MailerInterface::class),
            $bus ?? $this->makeBusStub(),
        );
    }

    public function testSendsEmailToAssignee(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask());

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser());

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        ($this->makeHandler(taskService: $taskService, userService: $userService, mailer: $mailer))($this->makeMessage());
    }

    public function testDispatchesNotificationDispatchMessage(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask('Deploy to production'));

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser('assignee@example.com'));

        $bus = $this->createMock(MessageBusInterface::class);
        $dispatched = null;
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($message) use (&$dispatched) {
                $dispatched = $message;
                return new Envelope($message);
            });

        ($this->makeHandler(taskService: $taskService, userService: $userService, bus: $bus))($this->makeMessage());

        $this->assertInstanceOf(NotificationDispatchMessage::class, $dispatched);
        $this->assertSame('task.assignee_added', $dispatched->event);
        $this->assertInstanceOf(MessageAction::class, $dispatched->action);
        $this->assertSame('assignee@example.com', $dispatched->action->recipient);
        $this->assertStringContainsString('Deploy to production', $dispatched->action->subject);
    }

    public function testDoesNothingWhenTaskNotFound(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn(null);

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser());

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        ($this->makeHandler(taskService: $taskService, userService: $userService, mailer: $mailer, bus: $bus))($this->makeMessage());
    }

    public function testDoesNothingWhenUserNotFound(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask());

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        ($this->makeHandler(taskService: $taskService, userService: $userService, mailer: $mailer, bus: $bus))($this->makeMessage());
    }
}
