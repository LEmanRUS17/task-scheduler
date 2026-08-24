<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Application\DTORequest\UpdateStatusRequestDTO;
use App\WorkflowFeature\Domain\Exception\WorkflowAccessDeniedException;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateWorkflowStatusController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly Security $security,
    ) {
    }

    #[Route('/workflows/{id}/statuses/{statusId}', name: 'workflow_update_status', methods: ['PUT'])]
    public function __invoke(
        string $id,
        string $statusId,
        #[MapRequestPayload] UpdateStatusRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $status = $this->workflowService->updateStatus($id, $statusId, $request, $userId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (WorkflowAccessDeniedException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_FORBIDDEN,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
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
