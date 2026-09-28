<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Domain\Template\NotificationScenario;
use App\NotificationFeature\Domain\Template\NotificationTemplateRenderer;
use App\NotificationFeature\Domain\Template\NotificationType;
use App\NotificationFeature\Domain\Template\NotificationVariable;
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
        private readonly NotificationTemplateRenderer $renderer,
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    public function __invoke(UserRegisteredMessage $message): void
    {
        $scenario = NotificationScenario::UserRegistered;
        $notification = $this->renderer->render($scenario, NotificationType::Email, [
            NotificationVariable::ConfirmationCode->value => $message->confirmationCode,
        ]);

        $this->mailer->send(
            (new Email())->to($message->email)->subject($notification->subject)->text($notification->body),
        );

        $this->defaultBus->dispatch(
            NotificationDispatchMessage::create(
                event: $scenario->value,
                action: new MessageAction(
                    channel: 'email',
                    recipient: $message->email,
                    subject: $notification->subject,
                    body: $notification->body,
                ),
            ),
        );
    }
}
