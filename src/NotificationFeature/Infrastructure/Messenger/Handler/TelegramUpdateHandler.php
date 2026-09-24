<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Interactor\HandleTelegramWebhookInteractor;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use App\NotificationFeature\Infrastructure\Messenger\Message\TelegramUpdateMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

/**
 * Queue counterpart of TelegramWebhookController: updates polled via getUpdates arrive
 * through telegram_updates_queue and go through the same webhook interactor.
 */
#[AsMessageHandler]
final class TelegramUpdateHandler
{
    public function __construct(
        private readonly HandleTelegramWebhookInteractor $interactor,
    ) {
    }

    public function __invoke(TelegramUpdateMessage $message): void
    {
        $update = TelegramUpdate::fromWebhookPayload($message->payload);

        if ($update === null) {
            return;
        }

        try {
            $this->interactor->handle($update);
        } catch (\Throwable $e) {
            // not retried: a state handler may already have replied to the chat, a retry
            // would send the reply twice — same reason the webhook never lets Telegram retry.
            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        }
    }
}
