<?php

declare(strict_types=1);

namespace App\NotificationFeature\Presentation\Controller;

use App\NotificationFeature\Domain\Exception\TelegramChatNotLinkedException;
use App\NotificationFeature\Domain\Interactor\UnlinkTelegramChatInteractor;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UnlinkTelegramChatController
{
    public function __construct(
        private readonly UnlinkTelegramChatInteractor $interactor,
        private readonly Security $security,
    ) {
    }

    #[Route('/telegram/link', name: 'telegram_unlink', methods: ['DELETE'])]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $this->interactor->unlink($userId);
        } catch (TelegramChatNotLinkedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
