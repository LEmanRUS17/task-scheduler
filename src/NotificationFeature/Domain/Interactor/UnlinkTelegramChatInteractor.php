<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Exception\TelegramChatNotLinkedException;
use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;

final class UnlinkTelegramChatInteractor
{
    public function __construct(
        private readonly TelegramChatRepositoryInterface $telegramChats,
        private readonly TelegramNotifierInterface $telegramNotifier,
    ) {
    }

    /**
     * Deletes the user's telegram_chat row, so the chat falls back to the Unlinked state
     * and can be linked again through a fresh /start <token>.
     *
     * @throws TelegramChatNotLinkedException when the user has no linked chat
     */
    public function unlink(string $userId): void
    {
        $chat = $this->telegramChats->findByUserId($userId);
        if ($chat === null) {
            throw TelegramChatNotLinkedException::forUserId($userId);
        }

        $this->telegramChats->delete($chat);

        $this->telegramNotifier->notify(
            $chat->chatId(),
            'This chat has been unlinked. Task notifications will no longer be sent here.',
        );
    }
}
