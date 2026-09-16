<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Telegram\State;

use App\NotificationFeature\Domain\Entity\TelegramChat;
use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Telegram\TelegramStateHandlerInterface;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;

/**
 * Handles messages from a chat that already has a telegram_chat row. There is no menu
 * to navigate yet — the bot only pushes task notifications — so this just acknowledges
 * that the chat is already linked.
 */
final class MainMenuStateHandler implements TelegramStateHandlerInterface
{
    public function __construct(
        private readonly TelegramNotifierInterface $telegramNotifier,
    ) {
    }

    public function state(): TelegramChatState
    {
        return TelegramChatState::MainMenu;
    }

    public function handle(TelegramUpdate $update, ?TelegramChat $chat): void
    {
        $this->telegramNotifier->notify(
            $update->chatId,
            'This chat is already linked. Task notifications are sent here automatically.',
        );
    }
}
