<?php

declare(strict_types=1);

namespace App\ProfileFeature\Presentation\Controller;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\ProfileFeature\Domain\Entity\Profile;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UploadAvatarController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly Security $security,
    ) {
    }

    #[Route('/profile/me/avatar', name: 'profile_me_avatar_upload', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return new JsonResponse(['message' => 'No valid file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $metadata = $this->fileService->uploadAvatar(
                Profile::class,
                $userId,
                $file->getPathname(),
                $file->getClientOriginalName(),
                (string) $file->getMimeType(),
                (int) $file->getSize(),
                $userId,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return new JsonResponse(
            [
                'id' => $metadata->getId(),
                'originalName' => $metadata->getOriginalName(),
                'mimeType' => $metadata->getMimeType(),
                'size' => $metadata->getSize(),
                'url' => '/profile/' . $userId . '/avatar',
            ],
            Response::HTTP_CREATED,
        );
    }
}
