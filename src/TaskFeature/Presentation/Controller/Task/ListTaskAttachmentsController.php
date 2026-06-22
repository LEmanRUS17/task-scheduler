<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListTaskAttachmentsController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly TaskServiceInterface $taskService,
    ) {
    }

    #[Route('/task/{taskId}/attachments', name: 'task_attachment_list', methods: ['GET'])]
    public function __invoke(string $taskId): JsonResponse
    {
        if ($this->taskService->getById($taskId) === null) {
            return new JsonResponse(['message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $attachments = array_map(
            static fn ($metadata) => [
                'id' => $metadata->getId(),
                'originalName' => $metadata->getOriginalName(),
                'mimeType' => $metadata->getMimeType(),
                'size' => $metadata->getSize(),
                'createdAt' => $metadata->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'url' => '/task/' . $taskId . '/attachments/' . $metadata->getId(),
            ],
            $this->fileService->listAttachments(Task::class, $taskId),
        );

        return new JsonResponse(['attachments' => $attachments], Response::HTTP_OK);
    }
}
