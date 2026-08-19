<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Domain\Port\TeamMembershipInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DetachWorkflowFromTeamController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly TeamMembershipInterface $membership,
        private readonly Security $security,
    ) {
    }

    #[Route('/workflows/{id}/teams/{teamId}', name: 'workflow_detach_team', methods: ['DELETE'])]
    public function __invoke(string $id, string $teamId): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        if (!$this->membership->isMember($teamId, $userId)) {
            return new JsonResponse(['message' => 'Not a team member'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->workflowService->detachFromTeam($id, $teamId, $userId);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
