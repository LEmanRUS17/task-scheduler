<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\NotificationFeature\Infrastructure\Messenger\Message\UserRegisteredMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class UserRegisteredHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    public function __invoke(UserRegisteredMessage $message): void
    {
        $subject = 'Confirm your registration';
        $body = sprintf(
            "Welcome to Task Scheduler!\n\n"
                . "Use the following code to complete your registration: %s\n\n"
                . 'The code is valid for 24 hours.',
            $message->confirmationCode,
        );

        $this->mailer->send(
            (new Email())->to($message->email)->subject($subject)->text($body),
        );

        $this->defaultBus->dispatch(
            NotificationDispatchMessage::create(
                event: 'user.registered',
                action: new MessageAction(
                    channel: 'email',
                    recipient: $message->email,
                    subject: $subject,
                    body: $body,
                ),
            ),
        );
    }
}
