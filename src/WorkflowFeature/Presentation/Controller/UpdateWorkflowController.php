<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeature\Application\DTORequest\UpdateWorkflowRequestDTO;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateWorkflowController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {
    }

    #[Route('/workflows/{id}', name: 'workflow_update', methods: ['PUT'])]
    public function __invoke(
        string $id,
        #[MapRequestPayload] UpdateWorkflowRequestDTO $request,
    ): JsonResponse {
        try {
            $workflow = $this->workflowService->update($id, $request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(
            [
                'id' => $workflow->getId(),
                'title' => $workflow->getTitle(),
                'createdBy' => $workflow->getCreatedBy(),
                'createdAt' => $workflow->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'description' => $workflow->getDescription(),
            ],
            Response::HTTP_OK,
        );
    }
}
