<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListWorkflowsController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {}

    #[Route('/workflows', name: 'workflow_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $workflows = $this->workflowService->getList();

        return new JsonResponse([
            'success' => true,
            'data' => array_map(
                fn($w) => [
                    'id' => $w->getId(),
                    'title' => $w->getTitle(),
                    'createdBy' => $w->getCreatedBy(),
                    'createdAt' => $w->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $workflows,
            ),
        ]);
    }
}
