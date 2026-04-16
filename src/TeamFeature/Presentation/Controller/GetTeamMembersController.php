<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller;

use App\TeamFeatureApi\Service\TeamServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTeamMembersController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
    ) {}

    #[Route('/team/{teamId}/members', name: 'team_members_list', methods: ['GET'])]
    public function __invoke(string $teamId): JsonResponse
    {
        try {
            $members = $this->teamService->getMembers($teamId);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse([
            'success' => true,
            'data' => array_map(
                fn($m) => [
                    'teamId' => $m->getTeamId(),
                    'userId' => $m->getUserId(),
                    'role' => $m->getRole(),
                    'joinedAt' => $m->getJoinedAt()->format(\DateTimeInterface::ATOM),
                ],
                $members,
            ),
        ], Response::HTTP_OK);
    }
}
