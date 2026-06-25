<?php

declare(strict_types=1);

namespace App\UserFeature\Presentation\Controller;

use App\UserFeature\Application\DTORequest\ChangePasswordRequestDTO;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Changes the authenticated user's password via PATCH /user/password.
 *
 * Accepts a JSON payload with currentPassword and newPassword fields. The
 * current password is verified against the stored hash before the new one is
 * applied. Returns 422 when validation fails or the current password is wrong.
 */
#[AsController]
final class ChangePasswordController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly Security $security,
    ) {
    }

    #[Route('/user/password', name: 'user_change_password', methods: ['PATCH'])]
    public function __invoke(
        #[MapRequestPayload] ChangePasswordRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $this->userService->changePassword($userId, $request);
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
                'message' => 'Password changed successfully',
            ],
            Response::HTTP_OK,
        );
    }
}
