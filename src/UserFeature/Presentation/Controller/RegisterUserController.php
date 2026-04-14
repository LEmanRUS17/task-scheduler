<?php

declare(strict_types=1);

namespace App\UserFeature\Presentation\Controller;

use App\UserFeature\Application\DTORequest\RegisterUserRequestDTO;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles new user registration via POST /auth/register.
 *
 * Accepts a JSON payload with email and plainPassword fields.
 * The request is deserialized into RegisterUserRequestDTO and passed
 * to UserServiceInterface, which performs the following steps:
 *   1. Validates the request format (email format, password length).
 *      Returns 422 with a field-level error map on failure.
 *   2. Checks that the email is not already taken.
 *      Returns 409 with an error message on conflict.
 *   3. Hashes the password, creates the User aggregate,
 *      persists it, and dispatches the UserRegistered domain event.
 *      Returns 201 on success.
 */
#[AsController]
final class RegisterUserController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    #[Route('/auth/register', name: 'user_register', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] RegisterUserRequestDTO $request,
    ): JsonResponse {
        try {
            $this->userService->register($request);
        } catch (\InvalidArgumentException $e) {

            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true)
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {

            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => $e->getMessage()
                ],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(
            [
                'success' => true,
                'variant' => 'success',
                'message' => 'Registration successful'
            ],
            Response::HTTP_CREATED,
        );
    }
}
