<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Domain\Template\NotificationScenario;
use App\NotificationFeature\Domain\Template\NotificationTemplateRenderer;
use App\NotificationFeature\Domain\Template\NotificationType;
use App\NotificationFeature\Domain\Template\NotificationVariable;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskAssigneeAddedMessage;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class TaskAssigneeAddedHandler
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly UserServiceInterface $userService,
        private readonly NotificationTemplateRenderer $renderer,
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    public function __invoke(TaskAssigneeAddedMessage $message): void
    {
        $task = $this->taskService->getById($message->taskId);
        $user = $this->userService->findById($message->userId);

        if ($task === null || $user === null) {
            return;
        }

        $scenario = NotificationScenario::TaskAssigneeAdded;
        $notification = $this->renderer->render($scenario, NotificationType::Email, [
            NotificationVariable::TaskTitle->value => $task->getTitle(),
        ]);

        $this->mailer->send(
            (new Email())->to($user->getEmail())->subject($notification->subject)->text($notification->body),
        );

        $this->defaultBus->dispatch(
            NotificationDispatchMessage::create(
                event: $scenario->value,
                action: new MessageAction(
                    channel: 'email',
                    recipient: $user->getEmail(),
                    subject: $notification->subject,
                    body: $notification->body,
                ),
            ),
        );
    }
}
