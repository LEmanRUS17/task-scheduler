<?php

declare(strict_types=1);

namespace App\NotificationFeature\Presentation\Controller;

use App\NotificationFeature\Domain\Interactor\CheckTelegramLinkStatusInteractor;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class TelegramCheckController
{
    public function __construct(
        private readonly CheckTelegramLinkStatusInteractor $interactor,
        private readonly Security $security,
    ) {
    }

    #[Route('/telegram/check', name: 'telegram_check', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        return new JsonResponse([
            'status' => $this->interactor->isLinked($userId),
        ]);
    }
}
