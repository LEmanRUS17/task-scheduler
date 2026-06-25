<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DeleteTeamAvatarController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/{teamId}/avatar', name: 'team_avatar_delete', methods: ['DELETE'])]
    public function __invoke(string $teamId): Response
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

        $this->fileService->deleteAvatar(Team::class, $teamId);

        return new Response(null, Response::HTTP_NO_CONTENT);
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
