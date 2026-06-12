<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTeamListController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {
        return;
    }

    #[Route('/team/list', name: 'team_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $teams = $this->teamService->getTeamsByUserId($userId);

        return new JsonResponse(
            [
                'teams' => array_map(
                    fn($team) => [
                        'id' => $team->getId(),
                        'title' => $team->getTitle(),
                        'status' => $team->getStatus(),
                        'createdAt' => $team->getCreatedAt()->format(\DateTimeInterface::ATOM),
                        'description' => $team->getDescription(),
                    ],
                    $teams,
                ),
            ],
            Response::HTTP_OK,
        );
    }
}
