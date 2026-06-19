<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetWorkflowController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {
    }

    #[Route('/workflows/{id}', name: 'workflow_get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $workflow = $this->workflowService->getById($id);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($workflow === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Workflow not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $workflow->getId(),
                'title' => $workflow->getTitle(),
                'createdBy' => $workflow->getCreatedBy(),
                'createdAt' => $workflow->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'description' => $workflow->getDescription(),
                'statuses' => array_map(
                    fn($s) => [
                        'id' => $s->getId(),
                        'label' => $s->getLabel(),
                        'isInitial' => $s->isInitial(),
                        'description' => $s->getDescription(),
                    ],
                    $this->workflowService->getStatuses($id),
                ),
                'transitions' => array_map(
                    fn($t) => [
                        'id' => $t->getId(),
                        'name' => $t->getName(),
                        'from' => $t->getFromStatusId(),
                        'to' => $t->getToStatusId(),
                        'description' => $t->getDescription(),
                    ],
                    $this->workflowService->getTransitions($id),
                ),
            ],
        ]);
    }
}
