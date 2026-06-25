<?php

declare(strict_types=1);

namespace App\UserFeature\Presentation\Controller;

use App\UserFeature\Application\DTORequest\RequestPasswordResetRequestDTO;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Starts the "forgot password" flow via POST /auth/password/forgot.
 *
 * Accepts a JSON payload with an email field. When the email belongs to a
 * known account, a one-time reset code is generated and emailed (via the
 * PasswordResetRequested domain event). The response is always 200 with a
 * generic message so the endpoint cannot be used to discover which emails
 * are registered.
 */
#[AsController]
final class RequestPasswordResetController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    #[Route('/auth/password/forgot', name: 'user_password_forgot', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] RequestPasswordResetRequestDTO $request,
    ): JsonResponse {
        $this->userService->requestPasswordReset($request);

        return new JsonResponse(
            [
                'message' => 'If the email is registered, a reset code has been sent',
            ],
            Response::HTTP_OK,
        );
    }
}
