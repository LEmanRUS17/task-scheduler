<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DeleteTaskController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
    ) {}

    #[Route('/task/{id}', name: 'task_delete', methods: ['DELETE'])]
    public function __invoke(string $id): JsonResponse
    {
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
