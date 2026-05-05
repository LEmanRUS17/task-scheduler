<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\TaskFeature\Infrastructure\Messenger\Message\TaskAssigneeAddedMessage;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class TaskAssigneeAddedHandler
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly UserServiceInterface $userService,
        private readonly MailerInterface $mailer,
    ) {}

    public function __invoke(TaskAssigneeAddedMessage $message): void
    {
        $task = $this->taskService->getById($message->taskId);
        $user = $this->userService->findById($message->userId);

        if ($task === null || $user === null) {
            return;
        }

        $email = (new Email())
            ->to($user->getEmail())
            ->subject(sprintf('You have been assigned to task "%s"', $task->getTitle()))
            ->text(sprintf('You have been assigned to task "%s".', $task->getTitle()));

        $this->mailer->send($email);
    }
}
