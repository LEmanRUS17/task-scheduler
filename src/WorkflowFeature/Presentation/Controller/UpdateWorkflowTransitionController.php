<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Application\DTORequest\UpdateTransitionRequestDTO;
use App\WorkflowFeature\Domain\Exception\WorkflowAccessDeniedException;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateWorkflowTransitionController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly Security $security,
    ) {
    }

    #[Route('/workflows/{id}/transitions/{transitionId}', name: 'workflow_update_transition', methods: ['PUT'])]
    public function __invoke(
        string $id,
        string $transitionId,
        #[MapRequestPayload] UpdateTransitionRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $transition = $this->workflowService->updateTransition($id, $transitionId, $request, $userId);
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
