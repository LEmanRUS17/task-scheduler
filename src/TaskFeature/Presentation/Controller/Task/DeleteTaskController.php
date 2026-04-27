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
final class DeleteTaskController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly Security $security,
    ) {}

    #[Route('/task/{id}', name: 'task_delete', methods: ['DELETE'])]
    public function __invoke(string $id): JsonResponse
    {
        $task = $this->taskService->getById($id);

        if ($task === null) {
            return new JsonResponse(['success' => false, 'message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->security->isGranted(TaskPermission::DELETE, $task)) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->taskService->deleteById($id);
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
                'success' => true
            ],
            Response::HTTP_OK,
        );
    }
}
