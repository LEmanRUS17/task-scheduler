<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Presentation\Formatter\TaskAttachmentFormatter;
use App\TaskFeature\Presentation\Formatter\TaskResponseFormatter;
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
        private readonly FileServiceInterface $fileService,
        private readonly TagServiceInterface $tagService,
    ) {
    }

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

        $files = array_map(
            static fn ($metadata) => TaskAttachmentFormatter::format($id, $metadata),
            $this->fileService->listImageAttachments(Task::class, $id),
        );

        $tagsByTask = $this->tagService->getEntityTagsByIds(TagServiceInterface::TYPE_TASK, [$id]);

        $payload = TaskResponseFormatter::format($task, $tagsByTask[$id] ?? []);
        $payload['files'] = $files;
        $payload['filesCount'] = count($files);

        return new JsonResponse($payload, Response::HTTP_OK);
    }
}
