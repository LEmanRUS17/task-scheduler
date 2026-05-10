<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Handler\TaskStatusChangedHandler;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\SubscriptionFeatureApi\DTOResponse\SubscriptionDataResponseInterface;
use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;
use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskStatusChangedMessage;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TaskStatusChangedHandlerTest extends TestCase
{
    private function makeMessage(): TaskStatusChangedMessage
    {
        return new TaskStatusChangedMessage(
            taskId: 'task-uuid',
            transitionId: 'transition-uuid',
            fromStatus: 'In Progress',
            toStatus: 'Done',
            workflowDefinitionTitle: 'Default',
            teamId: null,
        );
    }

    private function makeTask(string $title = 'Fix login bug'): TaskDataResponseInterface
    {
        $task = $this->createStub(TaskDataResponseInterface::class);
        $task->method('getTitle')->willReturn($title);
        return $task;
    }

    private function makeUser(string $email = 'subscriber@example.com'): UserDataResponseInterface
    {
        $user = $this->createStub(UserDataResponseInterface::class);
        $user->method('getId')->willReturn('user-uuid');
        $user->method('getEmail')->willReturn($email);
        return $user;
    }

    private function makeSubscription(array $channels, string $userId = 'user-uuid'): SubscriptionDataResponseInterface
    {
        $sub = $this->createStub(SubscriptionDataResponseInterface::class);
        $sub->method('getUserId')->willReturn($userId);
        $sub->method('getChannels')->willReturn($channels);
        return $sub;
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
        ?SubscriptionServiceInterface $subscriptionService = null,
        ?MailerInterface $mailer = null,
        ?MessageBusInterface $bus = null,
    ): TaskStatusChangedHandler {
        return new TaskStatusChangedHandler(
            $taskService ?? $this->createStub(TaskServiceInterface::class),
            $userService ?? $this->createStub(UserServiceInterface::class),
            $subscriptionService ?? $this->createStub(SubscriptionServiceInterface::class),
            $mailer ?? $this->createStub(MailerInterface::class),
            $bus ?? $this->makeBusStub(),
        );
    }

    public function testSendsEmailForEmailSubscriber(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask());

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser());

        $subscriptionService = $this->createStub(SubscriptionServiceInterface::class);
        $subscriptionService->method('getSubscriptionsForSubjectTransition')->willReturn([
            $this->makeSubscription([NotificationChannel::EMAIL->value]),
        ]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        ($this->makeHandler(
            taskService: $taskService,
            userService: $userService,
            subscriptionService: $subscriptionService,
            mailer: $mailer,
        ))($this->makeMessage());
    }

    public function testDispatchesNotificationMessageWithCorrectData(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask('Fix login bug'));

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser('sub@example.com'));

        $subscriptionService = $this->createStub(SubscriptionServiceInterface::class);
        $subscriptionService->method('getSubscriptionsForSubjectTransition')->willReturn([
            $this->makeSubscription([NotificationChannel::EMAIL->value]),
        ]);

        $bus = $this->createMock(MessageBusInterface::class);
        $dispatched = null;
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($message) use (&$dispatched) {
                $dispatched = $message;
                return new Envelope($message);
            });

        ($this->makeHandler(
            taskService: $taskService,
            userService: $userService,
            subscriptionService: $subscriptionService,
            bus: $bus,
        ))($this->makeMessage());

        $this->assertInstanceOf(NotificationDispatchMessage::class, $dispatched);
        $this->assertSame('task.status_changed', $dispatched->event);
        $this->assertInstanceOf(MessageAction::class, $dispatched->action);
        $this->assertSame('email', $dispatched->action->channel);
        $this->assertSame('sub@example.com', $dispatched->action->recipient);
        $this->assertStringContainsString('Fix login bug', $dispatched->action->subject);
        $this->assertStringContainsString('In Progress', $dispatched->action->body);
        $this->assertStringContainsString('Done', $dispatched->action->body);
    }

    public function testDispatchesOneMessagePerSubscriberChannel(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask());

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser());

        $subscriptionService = $this->createStub(SubscriptionServiceInterface::class);
        $subscriptionService->method('getSubscriptionsForSubjectTransition')->willReturn([
            $this->makeSubscription([NotificationChannel::EMAIL->value], 'user-1'),
            $this->makeSubscription([NotificationChannel::EMAIL->value], 'user-2'),
        ]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(fn($m) => new Envelope($m));

        ($this->makeHandler(taskService: $taskService, userService: $userService, subscriptionService: $subscriptionService, bus: $bus))($this->makeMessage());
    }

    public function testDoesNothingWhenTaskNotFound(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        ($this->makeHandler(taskService: $taskService, mailer: $mailer, bus: $bus))($this->makeMessage());
    }

    public function testSkipsSubscriptionWhenUserNotFound(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask());

        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn(null);

        $subscriptionService = $this->createStub(SubscriptionServiceInterface::class);
        $subscriptionService->method('getSubscriptionsForSubjectTransition')->willReturn([
            $this->makeSubscription([NotificationChannel::EMAIL->value]),
        ]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        ($this->makeHandler(
            taskService: $taskService,
            userService: $userService,
            subscriptionService: $subscriptionService,
            mailer: $mailer,
            bus: $bus,
        ))($this->makeMessage());
    }

    public function testDoesNothingWhenNoSubscriptions(): void
    {
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($this->makeTask());

        $subscriptionService = $this->createStub(SubscriptionServiceInterface::class);
        $subscriptionService->method('getSubscriptionsForSubjectTransition')->willReturn([]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        ($this->makeHandler(taskService: $taskService, subscriptionService: $subscriptionService, mailer: $mailer, bus: $bus))($this->makeMessage());
    }
}
