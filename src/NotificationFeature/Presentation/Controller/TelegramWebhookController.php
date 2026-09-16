<?php

declare(strict_types=1);

namespace App\NotificationFeature\Presentation\Controller;

use App\NotificationFeature\Domain\Interactor\HandleTelegramWebhookInteractor;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public endpoint registered as the bot's webhook with Telegram. Always answers 200 so
 * Telegram doesn't retry the update — errors are swallowed the same way as the job
 * reference implementation's actionWebhook() (see job vault: Modules/telegram/Webhook.md).
 */
#[AsController]
final class TelegramWebhookController
{
    public function __construct(
        private readonly HandleTelegramWebhookInteractor $interactor,
    ) {
    }

    #[Route('/telegram/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $update = \is_array($payload) ? TelegramUpdate::fromWebhookPayload($payload) : null;

        if ($update !== null) {
            try {
                $this->interactor->handle($update);
            } catch (\Throwable) {
                // swallowed: Telegram must still get a 200, see class docblock.
            }
        }

        return new JsonResponse(['success' => true]);
    }
}
