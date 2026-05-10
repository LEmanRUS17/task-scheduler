<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskCreatedMessage;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class TaskCreatedHandler
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $defaultBus,
    ) {}

    public function __invoke(TaskCreatedMessage $message): void
    {
        $user = $this->userService->findById($message->createdBy);

        if ($user === null) {
            return;
        }

        $subject = sprintf('Task "%s" created', $message->title);
        $body = sprintf('Your task "%s" has been successfully created.', $message->title);

        $this->mailer->send(
            (new Email())->to($user->getEmail())->subject($subject)->text($body),
        );

        $this->defaultBus->dispatch(
            NotificationDispatchMessage::create(
                event: 'task.created',
                action: new MessageAction(
                    channel: 'email',
                    recipient: $user->getEmail(),
                    subject: $subject,
                    body: $body,
                ),
            ),
        );
    }
}
