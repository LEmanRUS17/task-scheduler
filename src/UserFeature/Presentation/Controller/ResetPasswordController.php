<?php

declare(strict_types=1);

namespace App\UserFeature\Presentation\Controller;

use App\UserFeature\Application\DTORequest\ResetPasswordRequestDTO;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Completes the "forgot password" flow via POST /auth/password/reset.
 *
 * Accepts a JSON payload with email, code and newPassword fields. The code is
 * validated against the one stored for the account (and its expiry) before the
 * new password is applied. Returns 422 when validation fails or the code is
 * missing/invalid/expired.
 */
#[AsController]
final class ResetPasswordController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {
    }

    #[Route('/auth/password/reset', name: 'user_password_reset', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] ResetPasswordRequestDTO $request,
    ): JsonResponse {
        try {
            $this->userService->resetPassword($request);
        } catch (\DomainException $e) {
            return new JsonResponse(
                [
                    'message' => $e->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return new JsonResponse(
            [
                'message' => 'Password has been reset',
            ],
            Response::HTTP_OK,
        );
    }
}
