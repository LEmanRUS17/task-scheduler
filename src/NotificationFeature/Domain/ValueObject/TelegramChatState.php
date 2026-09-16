<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\ValueObject;

enum TelegramChatState: int
{
    /**
     * No telegram_chat row exists yet for the chat. Never persisted — used only to
     * route updates from a chat that hasn't been linked to a user through /start <token>.
     */
    case Unlinked = 0;

    case MainMenu = 1;
}
