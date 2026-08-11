<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\NotificationFeature\Infrastructure\Messenger\Message\TeamMemberInvitedMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class TeamMemberInvitedHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $defaultBus,
        private readonly string $frontendUrl,
    ) {
    }

    public function __invoke(TeamMemberInvitedMessage $message): void
    {
        $link = sprintf('%s/invitations/accept?token=%s', $this->frontendUrl, $message->token);

        $subject = sprintf('You have been invited to join "%s"', $message->teamTitle);
        $body = sprintf(
            "You have been invited to join the team \"%s\" on Task Scheduler.\n\n"
                . "Accept the invitation: %s\n\n"
                . "Or use this invitation code manually: %s\n\n"
                . "The invitation is valid for 7 days.\n\n"
                . 'If you did not expect this invitation, you can safely ignore this email.',
            $message->teamTitle,
            $link,
            $message->token,
        );

        $this->mailer->send(
            (new Email())->to($message->invitedEmail)->subject($subject)->text($body),
        );

        $this->defaultBus->dispatch(
            NotificationDispatchMessage::create(
                event: 'team.member_invited',
                action: new MessageAction(
                    channel: 'email',
                    recipient: $message->invitedEmail,
                    subject: $subject,
                    body: $body,
                ),
            ),
        );
    }
}
