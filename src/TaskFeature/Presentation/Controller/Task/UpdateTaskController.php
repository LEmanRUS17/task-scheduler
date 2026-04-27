<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeature\Application\DTORequest\TaskUpdateRequestDTO;
use App\TaskFeature\Domain\ValueObject\TaskPermission;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateTaskController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly Security $security,
    ) {}

    #[Route('/task/{id}', name: 'task_update', methods: ['PATCH'])]
    public function __invoke(
        string $id,
        #[MapRequestPayload] TaskUpdateRequestDTO $request,
    ): JsonResponse {
        $task = $this->taskService->getById($id);

        if ($task === null) {
            return new JsonResponse(['success' => false, 'message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->security->isGranted(TaskPermission::EDIT, $task)) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        try {
            $task = $this->taskService->update($id, $request);
        } catch (\InvalidArgumentException $e) {

            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
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
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'success' => true,
                'data' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus(),
                    'createdAt' => $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
            ],
            Response::HTTP_OK,
        );
    }
}
