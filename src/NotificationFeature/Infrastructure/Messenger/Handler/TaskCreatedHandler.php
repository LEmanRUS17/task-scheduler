<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\TaskFeature\Infrastructure\Messenger\Message\TaskCreatedMessage;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class TaskCreatedHandler
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly MailerInterface $mailer,
    ) {}

    public function __invoke(TaskCreatedMessage $message): void
    {
        $user = $this->userService->findById($message->createdBy);

        if ($user === null) {
            return;
        }

        $email = (new Email())
            ->to($user->getEmail())
            ->subject(sprintf('Task "%s" created', $message->title))
            ->text(sprintf('Your task "%s" has been successfully created.', $message->title));

        $this->mailer->send($email);
    }
}
