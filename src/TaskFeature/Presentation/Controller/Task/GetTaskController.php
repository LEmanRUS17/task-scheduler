<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTaskController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
    ) {}

    #[Route('/task/{id}', name: 'task_get', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $task = $this->taskService->getById($id);

        if ($task === null) {

            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Task not found'
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'id'            => $task->getId(),
                'title'         => $task->getTitle(),
                'status'        => $task->getStatus(),
                'priority'      => $task->getPriority(),
                'teamId'        => $task->getTeamId(),
                'createdBy'     => $task->getCreatedBy(),
                'assigneeIds'   => $task->getAssigneeIds(),
                'scheduledStart' => $task->getScheduledStart()?->format(\DateTimeInterface::ATOM),
                'scheduledEnd'  => $task->getScheduledEnd()?->format(\DateTimeInterface::ATOM),
                'estimatedTime' => $task->getEstimatedTime(),
                'actualTime'    => $task->getActualTime(),
                'createdAt'     => $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
            Response::HTTP_OK,
        );
    }
}
