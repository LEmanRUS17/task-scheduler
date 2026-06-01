<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Presentation\Controller;

use App\SubscriptionFeature\Application\DTORequest\SubscribeRequestDTO;
use App\SubscriptionFeature\Application\Exception\ValidationException;
use App\SubscriptionFeature\Presentation\Formatter\SubscriptionResponseFormatter;
use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class SubscribeController
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly Security $security,
    ) {
    }

    #[Route('/subscriptions', name: 'subscription_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] SubscribeRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $subscription = $this->subscriptionService->subscribe($request, $userId);
        } catch (ValidationException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->getViolations(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(
            SubscriptionResponseFormatter::format($subscription),
            Response::HTTP_CREATED,
        );
    }
}
