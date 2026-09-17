<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Telegram\State;

use App\NotificationFeature\Domain\Entity\TelegramChat;
use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\Repository\TelegramLinkTokenRepositoryInterface;
use App\NotificationFeature\Domain\Telegram\TelegramStateHandlerInterface;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;

/**
 * Handles a chat with no telegram_chat row, i.e. only the `/start <token>` deep link
 * created by CreateTelegramLinkTokenInteractor. On a valid, unexpired token it creates
 * the link and the chat moves to TelegramChatState::MainMenu from then on.
 */
final class UnlinkedStateHandler implements TelegramStateHandlerInterface
{
    private const string START_TOKEN_PATTERN = '/^\/start\s+(\S+)$/';

    public function __construct(
        private readonly TelegramLinkTokenRepositoryInterface $tokens,
        private readonly TelegramChatRepositoryInterface $telegramChats,
        private readonly TelegramNotifierInterface $telegramNotifier,
    ) {
    }

    public function state(): TelegramChatState
    {
        return TelegramChatState::Unlinked;
    }

    public function handle(TelegramUpdate $update, ?TelegramChat $chat): void
    {
        $token = $this->extractToken($update->text);

        if ($token === null) {
            $this->telegramNotifier->notify(
                $update->chatId,
                'Open this chat using the connect link from the app to link it to your account.',
            );

            return;
        }

        $userId = $this->tokens->findUserIdByToken($token);

        if ($userId === null) {
            $this->telegramNotifier->notify(
                $update->chatId,
                'This link has expired. Request a new one from the app.',
            );

            return;
        }

        $this->tokens->delete($token);

        if ($this->telegramChats->isLinkedForUserId($userId)) {
            $this->telegramNotifier->notify(
                $update->chatId,
                'This account already has a linked Telegram chat.',
            );

            return;
        }

        $this->telegramChats->save(TelegramChat::create($userId, $update->chatId, TelegramChatState::MainMenu));

        $this->telegramNotifier->notify(
            $update->chatId,
            'This chat is now linked. You will receive task notifications here.',
        );
    }

    private function extractToken(?string $text): ?string
    {
        if ($text !== null && preg_match(self::START_TOKEN_PATTERN, trim($text), $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
