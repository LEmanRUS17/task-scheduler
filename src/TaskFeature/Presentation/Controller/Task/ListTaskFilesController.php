<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Presentation\Formatter\TaskAttachmentFormatter;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListTaskFilesController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly TaskServiceInterface $taskService,
    ) {
    }

    #[Route('/task/{taskId}/files', name: 'task_file_list', methods: ['GET'])]
    public function __invoke(string $taskId): JsonResponse
    {
        if ($this->taskService->getById($taskId) === null) {
            return new JsonResponse(['message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $files = array_map(
            static fn ($metadata) => TaskAttachmentFormatter::format($taskId, $metadata),
            $this->fileService->listImageAttachments(Task::class, $taskId),
        );

        return new JsonResponse(
            ['files' => $files, 'count' => count($files)],
            Response::HTTP_OK,
        );
    }
}
