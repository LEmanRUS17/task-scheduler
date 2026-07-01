<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetWorkflowStatusController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {
    }

    #[Route('/workflows/{id}/statuses/{statusId}', name: 'workflow_get_status', methods: ['GET'])]
    public function __invoke(string $id, string $statusId): JsonResponse
    {
        try {
            $status = $this->workflowService->getStatusById($id, $statusId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if ($status === null) {
            return new JsonResponse(
                ['message' => 'Status not found'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'id' => $status->getId(),
                'workflowId' => $status->getWorkflowId(),
                'label' => $status->getLabel(),
                'isInitial' => $status->isInitial(),
                'isFinal' => $status->isFinal(),
                'createdAt' => $status->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'description' => $status->getDescription(),
            ],
            Response::HTTP_OK,
        );
    }
}
