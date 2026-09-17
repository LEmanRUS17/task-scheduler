<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;
use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskStatusChangedMessage;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class TaskStatusChangedHandler
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly UserServiceInterface $userService,
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly WorkflowServiceInterface $workflowService,
        private readonly TelegramChatRepositoryInterface $telegramChats,
        private readonly TelegramNotifierInterface $telegramNotifier,
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    public function __invoke(TaskStatusChangedMessage $message): void
    {
        $task = $this->taskService->getById($message->taskId);

        if ($task === null) {
            return;
        }

        $subscriptions = $this->subscriptionService->getSubscriptionsForSubjectTransition(
            subjectType: 'task',
            subjectId: $message->taskId,
            transitionId: $message->transitionId,
        );

        $isFinal = $this->isFinalStatus($message->workflowDefinitionTitle, $message->toStatus);

        $event = $isFinal ? 'task.completed' : 'task.status_changed';

        if ($isFinal) {
            $subject = sprintf('Task "%s" completed', $task->getTitle());
            $body = sprintf('Task "%s" has been completed.', $task->getTitle());
        } else {
            $subject = sprintf('Task "%s" status changed', $task->getTitle());
            $body = sprintf(
                'Task "%s" has been moved from "%s" to "%s".',
                $task->getTitle(),
                $message->fromStatus,
                $message->toStatus,
            );
        }

        foreach ($subscriptions as $subscription) {
            $user = $this->userService->findById($subscription->getUserId());

            if ($user === null) {
                continue;
            }

            foreach ($subscription->getChannels() as $channel) {
                $channelEnum = NotificationChannel::from((int) $channel);

                match ($channelEnum) {
                    NotificationChannel::EMAIL => $this->sendEmail($user->getEmail(), $subject, $body),
                    default => null,
                };

                $this->defaultBus->dispatch(
                    NotificationDispatchMessage::create(
                        event: $event,
                        action: new MessageAction(
                            channel: strtolower($channelEnum->name),
                            recipient: $user->getEmail(),
                            subject: $subject,
                            body: $body,
                        ),
                    ),
                );
            }

            $this->notifyTelegramIfLinked($subscription->getUserId(), $event, $subject, $body);
        }
    }

    private function isFinalStatus(string $workflowId, string $statusId): bool
    {
        try {
            return $this->workflowService->getStatusById($workflowId, $statusId)?->isFinal() ?? false;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    private function notifyTelegramIfLinked(string $userId, string $event, string $subject, string $body): void
    {
        $chat = $this->telegramChats->findByUserId($userId);

        if ($chat === null) {
            return;
        }

        $this->telegramNotifier->notify($chat->chatId(), $subject . "\n\n" . $body);

        $this->defaultBus->dispatch(
            NotificationDispatchMessage::create(
                event: $event,
                action: new MessageAction(
                    channel: 'telegram',
                    recipient: (string) $chat->chatId(),
                    subject: $subject,
                    body: $body,
                ),
            ),
        );
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        $this->mailer->send(
            (new Email())->to($to)->subject($subject)->text($body),
        );
    }
}
