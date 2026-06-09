<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTaskStatusHistoryController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
    ) {}

    #[Route('/task/{id}/status-history', name: 'task_status_history', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $task = $this->taskService->getById($id);

        if ($task === null) {
            return new JsonResponse(['message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $history = $this->taskService->getStatusHistory($id);

        return new JsonResponse(
            array_map(
                function ($entry) {
                    $profile = $entry->getProfile();

                    return [
                        'id' => $entry->getId(),
                        'transitionId' => $entry->getTransitionId(),
                        'toStatusLabel' => $entry->getToStatusLabel(),
                        'profile' => $profile === null ? null : [
                            'userId' => $profile->getUserId(),
                            'username' => $profile->getUsername(),
                        ],
                        'changedAt' => $entry->getChangedAt()->format(\DateTimeInterface::ATOM),
                    ];
                },
                $history,
            ),
            Response::HTTP_OK,
        );
    }
}
