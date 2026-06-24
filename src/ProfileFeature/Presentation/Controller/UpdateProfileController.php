<?php

declare(strict_types=1);

namespace App\ProfileFeature\Presentation\Controller;

use App\ProfileFeature\Application\DTORequest\UpdateProfileRequestDTO;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateProfileController
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService,
        private readonly Security $security,
    ) {}

    #[Route('/profile/me', name: 'profile_me_update', methods: ['PATCH'])]
    public function __invoke(
        #[MapRequestPayload] UpdateProfileRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $this->profileService->update($userId, $request);
            $profile = $this->profileService->getByUserId($userId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'userId' => $profile->getUserId(),
                'username' => $profile->getUsername(),
                'firstname' => $profile->getFirstname(),
                'lastname' => $profile->getLastname(),
                'midlname' => $profile->getMidlname(),
                'status' => $profile->getStatus(),
                'lastLogin' => $profile->getLastLogin()?->format(\DateTimeInterface::ATOM),
                'avatar'    => $profile->getAvatar()?->getUrl(),
            ],
            Response::HTTP_OK,
        );
    }
}
