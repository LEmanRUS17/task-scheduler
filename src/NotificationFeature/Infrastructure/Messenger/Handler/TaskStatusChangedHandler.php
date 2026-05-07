<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskStatusChangedMessage;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class TaskStatusChangedHandler
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly UserServiceInterface $userService,
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly MailerInterface $mailer,
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

        foreach ($subscriptions as $subscription) {
            $user = $this->userService->findById($subscription->getUserId());

            if ($user === null) {
                continue;
            }

            foreach ($subscription->getChannels() as $channel) {
                match ($channel) {
                    'email' => $this->sendEmail(
                        $user->getEmail(),
                        $task->getTitle(),
                        $message->fromStatus,
                        $message->toStatus,
                    ),
                    default => null,
                };
            }
        }
    }

    private function sendEmail(string $to, string $taskTitle, string $from, string $to_status): void
    {
        $this->mailer->send(
            (new Email())
                ->to($to)
                ->subject(sprintf('Task "%s" status changed', $taskTitle))
                ->text(sprintf('Task "%s" has been moved from "%s" to "%s".', $taskTitle, $from, $to_status)),
        );
    }
}
