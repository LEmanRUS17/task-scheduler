<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Presentation\Controller;

use App\SubscriptionFeature\Application\DTORequest\UpdateSubscriptionRequestDTO;
use App\SubscriptionFeature\Application\Exception\ValidationException;
use App\SubscriptionFeature\Domain\Exception\SubscriptionAccessDeniedException;
use App\SubscriptionFeature\Domain\Exception\SubscriptionNotFoundException;
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
final class UpdateSubscriptionController
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly Security $security,
    ) {
    }

    #[Route('/subscriptions/{subscriptionId}', name: 'subscription_update', methods: ['PUT'])]
    public function __invoke(
        string $subscriptionId,
        #[MapRequestPayload] UpdateSubscriptionRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $subscription = $this->subscriptionService->update($subscriptionId, $request, $userId);
        } catch (ValidationException $e) {
            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => 'Validation failed',
                    'errors' => $e->getViolations(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (SubscriptionNotFoundException $e) {
            return new JsonResponse(
                ['success' => false, 'variant' => 'danger', 'message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        } catch (SubscriptionAccessDeniedException $e) {
            return new JsonResponse(
                ['success' => false, 'variant' => 'danger', 'message' => $e->getMessage()],
                Response::HTTP_FORBIDDEN,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['success' => false, 'variant' => 'danger', 'message' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return new JsonResponse(
            ['success' => true, 'variant' => 'success', 'data' => SubscriptionResponseFormatter::format($subscription)],
            Response::HTTP_OK,
        );
    }
}
