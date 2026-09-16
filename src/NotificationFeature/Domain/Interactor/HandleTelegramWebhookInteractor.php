<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\Telegram\TelegramStateHandlerInterface;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class HandleTelegramWebhookInteractor
{
    /**
     * @param iterable<TelegramStateHandlerInterface> $stateHandlers
     */
    public function __construct(
        private readonly TelegramChatRepositoryInterface $telegramChats,
        #[AutowireIterator('app.telegram_state_handler')]
        private readonly iterable $stateHandlers,
    ) {
    }

    public function handle(TelegramUpdate $update): void
    {
        $chat = $this->telegramChats->findByChatId($update->chatId);
        $state = $chat?->state() ?? TelegramChatState::Unlinked;

        $this->resolveHandler($state)->handle($update, $chat);
    }

    private function resolveHandler(TelegramChatState $state): TelegramStateHandlerInterface
    {
        foreach ($this->stateHandlers as $handler) {
            if ($handler->state() === $state) {
                return $handler;
            }
        }

        throw new \DomainException("No Telegram state handler registered for state \"{$state->name}\".");
    }
}
