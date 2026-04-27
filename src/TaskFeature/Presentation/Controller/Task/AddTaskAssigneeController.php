<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeature\Domain\ValueObject\TaskPermission;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AddTaskAssigneeController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly Security $security,
    ) {}

    #[Route('/task/{taskId}/assignee/{userId}', name: 'task_assignee_add', methods: ['POST'])]
    public function __invoke(string $taskId, string $userId): JsonResponse
    {
        $task = $this->taskService->getById($taskId);

        if ($task === null) {
            return new JsonResponse(['success' => false, 'message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->security->isGranted(TaskPermission::EDIT, $task)) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

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
