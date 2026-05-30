<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Presentation\Controller;

use App\SubscriptionFeature\Domain\Exception\SubscriptionAccessDeniedException;
use App\SubscriptionFeature\Domain\Exception\SubscriptionNotFoundException;
use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UnsubscribeController
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly Security $security,
    ) {
    }

    #[Route('/subscriptions/{subscriptionId}', name: 'subscription_delete', methods: ['DELETE'])]
    public function __invoke(string $subscriptionId): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $this->subscriptionService->unsubscribe($subscriptionId, $userId);
        } catch (SubscriptionNotFoundException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        } catch (SubscriptionAccessDeniedException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_FORBIDDEN,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
