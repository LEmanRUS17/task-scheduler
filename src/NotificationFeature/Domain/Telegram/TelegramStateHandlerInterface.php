<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Telegram;

use App\NotificationFeature\Domain\Entity\TelegramChat;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * One handler per TelegramChatState. HandleTelegramWebhookInteractor resolves which
 * state a chat is in by looking it up in the database (its telegram_chat row, if any)
 * and dispatches to the matching handler — the State pattern used by the reference
 * bot implementation (job vault: Context + StateMachine in "Авторизация через
 * Telegram"), where "the StateMachine determines which state to use based on which
 * state ran before."
 */
#[AutoconfigureTag('app.telegram_state_handler')]
interface TelegramStateHandlerInterface
{
    public function state(): TelegramChatState;

    /**
     * $chat is null exactly when state() === TelegramChatState::Unlinked, since that
     * state exists only for chats with no telegram_chat row yet.
     */
    public function handle(TelegramUpdate $update, ?TelegramChat $chat): void;
}
