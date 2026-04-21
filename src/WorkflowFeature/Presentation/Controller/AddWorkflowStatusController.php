<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeature\Application\DTORequest\AddStatusRequestDTO;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AddWorkflowStatusController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {}

    #[Route('/workflows/{id}/statuses', name: 'workflow_add_status', methods: ['POST'])]
    public function __invoke(
        string $id,
        #[MapRequestPayload] AddStatusRequestDTO $request,
    ): JsonResponse {
        try {
            $status = $this->workflowService->addStatus($id, $request);
        } catch (\InvalidArgumentException $e) {

            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {

            return new JsonResponse(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(
            [
                'success' => true,
                'data' => [
                    'id' => $status->getId(),
                    'workflowId' => $status->getWorkflowId(),
                    'label' => $status->getLabel(),
                    'isInitial' => $status->isInitial(),
                    'createdAt' => $status->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
            ],
            Response::HTTP_CREATED,
        );
    }
}
