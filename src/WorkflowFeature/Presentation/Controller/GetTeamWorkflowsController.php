<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Domain\Port\TeamMembershipInterface;
use App\WorkflowFeatureApi\DTOResponse\TeamWorkflowResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTeamWorkflowsController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly TeamMembershipInterface $membership,
        private readonly Security $security,
    ) {
    }

    #[Route('/workflows/teams/{teamId}', name: 'workflow_list_by_team', methods: ['GET'])]
    public function __invoke(string $teamId): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        if (!$this->membership->isMember($teamId, $userId)) {
            return new JsonResponse(['message' => 'Not a team member'], Response::HTTP_FORBIDDEN);
        }

        $workflows = $this->workflowService->getTeamWorkflows($teamId);

        return new JsonResponse([
            'workflows' => array_map(
                static fn(TeamWorkflowResponseInterface $w) => [
                    'id' => $w->getWorkflowId(),
                    'title' => $w->getTitle(),
                    'attachedBy' => $w->getAttachedBy(),
                    'attachedAt' => $w->getAttachedAt()->format(\DateTimeInterface::ATOM),
                    'taskCount' => $w->getTaskCount(),
                ],
                $workflows,
            ),
        ]);
    }
}
