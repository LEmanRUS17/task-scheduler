<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Presentation\Controller;

use App\SubscriptionFeature\Presentation\Formatter\SubscriptionResponseFormatter;
use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetSubscriptionController
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    #[Route('/subscriptions/{subscriptionId}', name: 'subscription_get', methods: ['GET'])]
    public function __invoke(string $subscriptionId): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->getById($subscriptionId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['success' => false, 'variant' => 'danger', 'message' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($subscription === null) {
            return new JsonResponse(
                ['success' => false, 'variant' => 'danger', 'message' => "Subscription {$subscriptionId} not found"],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            ['success' => true, 'data' => SubscriptionResponseFormatter::format($subscription)],
            Response::HTTP_OK,
        );
    }
}
