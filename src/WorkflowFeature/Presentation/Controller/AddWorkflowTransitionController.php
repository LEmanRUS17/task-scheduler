<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\WorkflowFeature\Application\DTORequest\AddTransitionRequestDTO;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AddWorkflowTransitionController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
    ) {}

    #[Route('/workflows/{id}/transitions', name: 'workflow_add_transition', methods: ['POST'])]
    public function __invoke(
        string $id,
        #[MapRequestPayload] AddTransitionRequestDTO $request,
    ): JsonResponse {
        try {
            $transition = $this->workflowService->addTransition($id, $request);
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
                    'id' => $transition->getId(),
                    'workflowId' => $transition->getWorkflowId(),
                    'name' => $transition->getName(),
                    'fromStatusLabel' => $transition->getFromStatusLabel(),
                    'toStatusLabel' => $transition->getToStatusLabel(),
                    'createdAt' => $transition->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
            ],
            Response::HTTP_CREATED,
        );
    }
}
