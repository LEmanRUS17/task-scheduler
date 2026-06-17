<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetWorkflowTransitionController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {
    }

    #[Route('/workflows/{id}/transitions/{transitionId}', name: 'workflow_get_transition', methods: ['GET'])]
    public function __invoke(string $id, string $transitionId): JsonResponse
    {
        try {
            $transition = $this->workflowService->getTransitionById($id, $transitionId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if ($transition === null) {
            return new JsonResponse(
                ['message' => 'Transition not found'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'id' => $transition->getId(),
                'workflowId' => $transition->getWorkflowId(),
                'name' => $transition->getName(),
                'fromStatusId' => $transition->getFromStatusId(),
                'toStatusId' => $transition->getToStatusId(),
                'createdAt' => $transition->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'description' => $transition->getDescription(),
            ],
            Response::HTTP_OK,
        );
    }
}
