<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AddTaskAssigneeController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
    ) {}

    #[Route('/task/{taskId}/assignee/{userId}', name: 'task_assignee_add', methods: ['POST'])]
    public function __invoke(string $taskId, string $userId): JsonResponse
    {
        try {
            $this->taskService->addAssignee($taskId, $userId);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(['success' => true], Response::HTTP_CREATED);
    }
}
