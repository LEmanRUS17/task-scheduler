<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

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
        private readonly MailerInterface $mailer,
    ) {}

    public function __invoke(TaskStatusChangedMessage $message): void
    {
        $task = $this->taskService->getById($message->taskId);

        if ($task === null) {
            return;
        }

        foreach ($task->getAssigneeIds() as $userId) {
            $user = $this->userService->findById($userId);

            if ($user === null) {
                continue;
            }

            $email = (new Email())
                ->to($user->getEmail())
                ->subject(sprintf('Task "%s" status changed', $task->getTitle()))
                ->text(sprintf(
                    'Task "%s" has been moved from "%s" to "%s".',
                    $task->getTitle(),
                    $message->fromStatus,
                    $message->toStatus,
                ));

            $this->mailer->send($email);
        }
    }
}
