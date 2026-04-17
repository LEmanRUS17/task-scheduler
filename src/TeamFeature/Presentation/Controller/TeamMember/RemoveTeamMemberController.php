<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\TeamMember;

use App\TeamFeatureApi\Service\TeamServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class RemoveTeamMemberController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
    ) {}

    #[Route('/team/{teamId}/member/{userId}', name: 'team_member_remove', methods: ['DELETE'])]
    public function __invoke(string $teamId, string $userId): JsonResponse
    {
        try {
            $this->teamService->removeMember($teamId, $userId);
        } catch (\DomainException $e) {
            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => $e->getMessage()
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse([
            'success' => true,
            'variant' => 'success',
            'message' => 'The user has been removed from the group'
        ], Response::HTTP_NO_CONTENT);
    }
}
