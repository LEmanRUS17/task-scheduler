<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[AsController]
final class DeleteTeamController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {}

    #[Route('/team/{id}', name: 'team_delete', methods: ['DELETE'])]
    public function __invoke(string $id): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $this->teamService->deleteByIdForUser($id, $userId);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
