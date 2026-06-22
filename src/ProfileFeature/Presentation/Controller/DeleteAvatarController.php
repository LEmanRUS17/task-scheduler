<?php

declare(strict_types=1);

namespace App\ProfileFeature\Presentation\Controller;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\ProfileFeature\Domain\Entity\Profile;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DeleteAvatarController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly Security $security,
    ) {
    }

    #[Route('/profile/me/avatar', name: 'profile_me_avatar_delete', methods: ['DELETE'])]
    public function __invoke(): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $this->fileService->deleteAvatar(Profile::class, $userId);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
