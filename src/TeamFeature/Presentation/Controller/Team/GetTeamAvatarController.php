<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\FileFeatureApi\Contract\ImageSize;
use App\TeamFeature\Domain\Entity\Team;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTeamAvatarController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {
    }

    #[Route('/team/{teamId}/avatar', name: 'team_avatar_get', methods: ['GET'])]
    public function __invoke(string $teamId, Request $request): Response
    {
        if ($this->fileService->getAvatar(Team::class, $teamId) === null) {
            return new JsonResponse(['message' => 'Avatar not found'], Response::HTTP_NOT_FOUND);
        }

        $size = ImageSize::fromName($request->query->get('size'));
        $path = $this->fileService->avatarImagePath(Team::class, $teamId, $size);

        if ($path === null || !is_file($path)) {
            return new JsonResponse(['message' => 'Avatar not found'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', 'image/webp');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'avatar-' . $size->value . '.webp',
        );

        return $response;
    }
}
