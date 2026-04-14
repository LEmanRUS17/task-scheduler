<?php

declare(strict_types=1);

namespace App\ProfileFeature\Presentation\Controller;

use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles user profile retrieval via GET /profile/{userId}.
 *
 * Accepts a userId route parameter and delegates to ProfileServiceInterface,
 * which performs the following steps:
 *   1. Looks up the profile by userId in the repository.
 *      Returns 404 if no profile is found for the given userId.
 *   2. Maps the Profile aggregate to ProfileDataResponseInterface
 *      and returns it with a 200 response.
 */
#[AsController]
final class GetProfileController
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService,
    ) {}

    #[Route('/profile/{userId}', name: 'profile_get', methods: ['GET'])]
    public function __invoke(string $userId): JsonResponse
    {
        try {
            $profile = $this->profileService->getByUserId($userId);
        } catch (\DomainException $e) {

            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => $e->getMessage(),
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'userId'    => $profile->getUserId(),
                'username'  => $profile->getUsername(),
                'firstname' => $profile->getFirstname(),
                'lastname'  => $profile->getLastname(),
                'midlname'  => $profile->getMidlname(),
                'status'    => $profile->getStatus(),
                'lastLogin' => $profile->getLastLogin()?->format(\DateTimeInterface::ATOM),
            ],
            Response::HTTP_OK,
        );
    }
}
