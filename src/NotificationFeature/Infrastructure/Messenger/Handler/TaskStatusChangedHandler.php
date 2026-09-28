<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\Template\NotificationScenario;
use App\NotificationFeature\Domain\Template\NotificationTemplateRenderer;
use App\NotificationFeature\Domain\Template\NotificationType;
use App\NotificationFeature\Domain\Template\NotificationVariable;
use App\NotificationFeature\Domain\Template\RenderedNotification;
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
        private readonly NotificationTemplateRenderer $renderer,
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

        if ($this->isFinalStatus($message->workflowDefinitionTitle, $message->toStatus)) {
            $scenario = NotificationScenario::TaskCompleted;
            $values = [NotificationVariable::TaskTitle->value => $task->getTitle()];
        } else {
            $scenario = NotificationScenario::TaskStatusChanged;
            $values = [
                NotificationVariable::TaskTitle->value => $task->getTitle(),
                NotificationVariable::FromStatus->value => $message->fromStatus,
                NotificationVariable::ToStatus->value => $message->toStatus,
            ];
        }

        foreach ($subscriptions as $subscription) {
            $user = $this->userService->findById($subscription->getUserId());

            if ($user === null) {
                continue;
            }

            foreach ($subscription->getChannels() as $channel) {
                $channelEnum = NotificationChannel::from((int) $channel);

                $notification = $this->renderer->render($scenario, match ($channelEnum) {
                    NotificationChannel::EMAIL => NotificationType::Email,
                    NotificationChannel::IN_APP => NotificationType::Push,
                }, $values);

                match ($channelEnum) {
                    NotificationChannel::EMAIL => $this->sendEmail($user->getEmail(), $notification),
                    default => null,
                };

                $this->defaultBus->dispatch(
                    NotificationDispatchMessage::create(
                        event: $scenario->value,
                        action: new MessageAction(
                            channel: strtolower($channelEnum->name),
                            recipient: $user->getEmail(),
                            subject: $notification->subject,
                            body: $notification->body,
                        ),
                    ),
                );
            }

            $this->notifyTelegramIfLinked($subscription->getUserId(), $scenario, $values);
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

    /**
     * @param array<value-of<NotificationVariable>, string> $values
     */
    private function notifyTelegramIfLinked(string $userId, NotificationScenario $scenario, array $values): void
    {
        $chat = $this->telegramChats->findByUserId($userId);

        if ($chat === null) {
            return;
        }

        $notification = $this->renderer->render($scenario, NotificationType::Messenger, $values);

        $this->telegramNotifier->notify($chat->chatId(), $notification->body);

        $this->defaultBus->dispatch(
            NotificationDispatchMessage::create(
                event: $scenario->value,
                action: new MessageAction(
                    channel: 'telegram',
                    recipient: (string) $chat->chatId(),
                    subject: $notification->subject,
                    body: $notification->body,
                ),
            ),
        );
    }

    private function sendEmail(string $to, RenderedNotification $notification): void
    {
        $this->mailer->send(
            (new Email())->to($to)->subject($notification->subject)->text($notification->body),
        );
    }
}
