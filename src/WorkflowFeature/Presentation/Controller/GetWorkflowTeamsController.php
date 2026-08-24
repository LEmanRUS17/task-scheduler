<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeatureApi\DTOResponse\AttachedTeamResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetWorkflowTeamsController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {
    }

    #[Route('/workflows/{id}/teams', name: 'workflow_get_teams', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $teams = $this->workflowService->getWorkflowTeams($id);

        return new JsonResponse([
            'teams' => array_map(
                static fn(AttachedTeamResponseInterface $t) => [
                    'id' => $t->getTeamId(),
                    'title' => $t->getTeamTitle(),
                    'attachedAt' => $t->getAttachedAt()->format(\DateTimeInterface::ATOM),
                ],
                $teams,
            ),
        ]);
    }
}
