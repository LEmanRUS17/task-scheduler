<?php

declare(strict_types=1);

namespace App\NotificationFeature\Presentation\Controller;

use App\NotificationFeature\Domain\Exception\TelegramChatAlreadyLinkedException;
use App\NotificationFeature\Domain\Interactor\CreateTelegramLinkTokenInteractor;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Creates the token the user embeds in https://t.me/<bot>?start=<token> to link their
 * Telegram chat. TelegramWebhookController redeems it once the user sends /start.
 */
#[AsController]
final class CreateTelegramLinkTokenController
{
    public function __construct(
        private readonly CreateTelegramLinkTokenInteractor $interactor,
        private readonly Security $security,
    ) {
    }

    #[Route('/telegram/link-token', name: 'telegram_create_link_token', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $token = $this->interactor->create($userId);
        } catch (TelegramChatAlreadyLinkedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'success' => true,
            'token' => $token,
        ]);
    }
}
