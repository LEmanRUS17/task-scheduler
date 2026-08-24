<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeatureApi\DTOResponse\WorkflowListMiniItemResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListTransactionByWorkflowController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {
    }

    #[Route(
        '/workflows/{id}/list-transaction-by-workflow',
        name: 'list_transaction_by_workflow',
        methods: ['GET'],
    )]
    public function __invoke(string $id): JsonResponse
    {
        $response = $this->workflowService->listTransactionByWorkflow($id);

        return new JsonResponse(
            [
                'transitions' => array_map(
                    static fn(WorkflowListMiniItemResponseInterface $t) => [
                        'id' => $t->getId(),
                        'name' => $t->getName(),
                    ],
                    $response->getTransitions(),
                ),
                'count' => $response->getCount(),
            ],
            Response::HTTP_OK,
        );
    }
}
