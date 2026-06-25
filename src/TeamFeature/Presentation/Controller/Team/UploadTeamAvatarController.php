<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UploadTeamAvatarController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/{teamId}/avatar', name: 'team_avatar_upload', methods: ['POST'])]
    public function __invoke(string $teamId, Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        if ($this->teamService->getById($teamId) === null) {
            return new JsonResponse(['message' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isMember($teamId, $userId)) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return new JsonResponse(['message' => 'No valid file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $metadata = $this->fileService->uploadAvatar(
                Team::class,
                $teamId,
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
                'url' => '/team/' . $teamId . '/avatar',
            ],
            Response::HTTP_CREATED,
        );
    }

    private function isMember(string $teamId, string $userId): bool
    {
        foreach ($this->teamService->getMembers($teamId) as $member) {
            if ($member->getUserId() === $userId) {
                return true;
            }
        }

        return false;
    }
}
