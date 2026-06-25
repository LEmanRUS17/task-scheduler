<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\NotificationFeature\Infrastructure\Messenger\Message\PasswordResetRequestedMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class PasswordResetRequestedHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    public function __invoke(PasswordResetRequestedMessage $message): void
    {
        $subject = 'Reset your password';
        $body = sprintf(
            "We received a request to reset your Task Scheduler password.\n\n"
                . "Use the following code to choose a new password: %s\n\n"
                . "The code is valid for 1 hour.\n\n"
                . 'If you did not request this, you can safely ignore this email.',
            $message->resetCode,
        );

        $this->mailer->send(
            (new Email())->to($message->email)->subject($subject)->text($body),
        );

        $this->defaultBus->dispatch(
            NotificationDispatchMessage::create(
                event: 'user.password_reset_requested',
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
