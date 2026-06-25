<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\ValueObject\TaskPermission;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DeleteTaskAttachmentController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly TaskServiceInterface $taskService,
        private readonly Security $security,
    ) {
    }

    #[Route('/task/{taskId}/attachments/{fileId}', name: 'task_attachment_delete', methods: ['DELETE'])]
    public function __invoke(string $taskId, string $fileId): Response
    {
        $task = $this->taskService->getById($taskId);

        if ($task === null) {
            return new JsonResponse(['message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->security->isGranted(TaskPermission::EDIT, $task)) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $metadata = $this->fileService->getFile($fileId);

        if (
            $metadata === null
            || $metadata->getEntityClass() !== Task::class
            || $metadata->getEntityId() !== $taskId
        ) {
            return new JsonResponse(['message' => 'Attachment not found'], Response::HTTP_NOT_FOUND);
        }

        $this->fileService->deleteFile($fileId);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
