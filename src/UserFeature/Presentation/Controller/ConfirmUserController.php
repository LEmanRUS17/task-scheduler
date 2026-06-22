<?php

declare(strict_types=1);

namespace App\UserFeature\Presentation\Controller;

use App\UserFeature\Application\DTORequest\ConfirmUserRequestDTO;
use App\UserFeature\Infrastructure\Security\UserProvider;
use App\UserFeatureApi\Service\UserServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Completes registration via POST /auth/confirm.
 *
 * Accepts a JSON payload with email and code fields. On success the user is
 * activated and immediately logged in: the same JWT (and refresh token)
 * response produced by /auth/login is returned, so the client does not need a
 * separate login round-trip.
 *
 * Returns 422 when the code is missing/invalid/expired.
 */
#[AsController]
final class ConfirmUserController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly UserProvider $userProvider,
        private readonly AuthenticationSuccessHandler $successHandler,
    ) {
    }

    #[Route('/auth/confirm', name: 'user_confirm', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] ConfirmUserRequestDTO $request,
    ): Response {
        try {
            $this->userService->confirm($request);
        } catch (\DomainException $e) {
            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => $e->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = $this->userProvider->loadUserByIdentifier($request->getEmail());

        return $this->successHandler->handleAuthenticationSuccess($user);
    }
}
