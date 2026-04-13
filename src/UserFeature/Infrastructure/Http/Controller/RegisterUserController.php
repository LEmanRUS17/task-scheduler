<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Http\Controller;

use App\UserFeature\Infrastructure\Http\Request\RegisterUserRequest;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class RegisterUserController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    #[Route('/auth/register', name: 'user_register', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] RegisterUserRequest $request,
    ): JsonResponse {
        try {
            $this->userService->register($request);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(
            ['success' => true],
            Response::HTTP_CREATED,
        );
    }
}
