<?php

declare(strict_types=1);

namespace App\UserFeature\Presentation\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Route stub for POST /auth/token/refresh.
 *
 * RouterListener requires the route to exist before the security Firewall
 * can intercept the request. The actual token rotation is handled by the
 * refresh_jwt authenticator configured in security.yaml — this body is never executed.
 */
#[AsController]
final class RefreshTokenController
{
    #[Route('/auth/token/refresh', name: 'auth_token_refresh', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'variant' => 'danger',
            'message' => 'unauthorized',
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
