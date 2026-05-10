<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;
use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskStatusChangedMessage;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
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
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $defaultBus,
    ) {}

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

        $subject = sprintf('Task "%s" status changed', $task->getTitle());
        $body = sprintf(
            'Task "%s" has been moved from "%s" to "%s".',
            $task->getTitle(),
            $message->fromStatus,
            $message->toStatus,
        );

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
                        event: 'task.status_changed',
                        action: new MessageAction(
                            channel: strtolower($channelEnum->name),
                            recipient: $user->getEmail(),
                            subject: $subject,
                            body: $body,
                        ),
                    ),
                );
            }
        }
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        $this->mailer->send(
            (new Email())->to($to)->subject($subject)->text($body),
        );
    }
}
