<?php

declare(strict_types=1);

namespace App\ProfileFeature\Presentation\Controller;

use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles retrieval of the authenticated user's own profile via GET /profile/me.
 *
 * Resolves the current user from the security context and delegates to
 * ProfileServiceInterface, which performs the following steps:
 *   1. Extracts the user ID from the JWT token via SecurityUser.
 *   2. Looks up the profile by that user ID in the repository.
 *      Returns 404 if no profile exists for the authenticated user.
 *   3. Maps the Profile aggregate to ProfileDataResponseInterface
 *      and returns it with a 200 response.
 */
#[AsController]
final class MeProfileController
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService,
        private readonly Security $security,
    ) {}

    #[Route('/profile/me', name: 'profile_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

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
