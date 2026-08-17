<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Presentation\Controller;

use App\SubscriptionFeature\Presentation\Formatter\SubscriptionResponseFormatter;
use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetSubscriptionsController
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly Security $security,
    ) {
    }

    #[Route('/subscriptions', name: 'subscription_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $subscriptions = $this->subscriptionService->getUserSubscriptions($userId);

        return new JsonResponse(
            array_map(SubscriptionResponseFormatter::format(...), $subscriptions),
            Response::HTTP_OK,
        );
    }
}
