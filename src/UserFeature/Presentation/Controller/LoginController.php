<?php

declare(strict_types=1);

namespace App\UserFeature\Presentation\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Route stub for POST /auth/login.
 *
 * Symfony's RouterListener requires the route to exist before
 * the security Firewall can intercept the request. The actual
 * authentication is handled by the json_login authenticator configured in
 * security.yaml — this method body is never executed.
 */
#[AsController]
final class LoginController
{
    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'variant' => 'danger',
            'message' => 'unauthorized'
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
