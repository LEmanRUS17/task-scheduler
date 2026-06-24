<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\FileFeatureApi\Contract\FileMetadataInterface;
use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\ValueObject\TaskPermission;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UploadTaskAttachmentController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly TaskServiceInterface $taskService,
        private readonly Security $security,
    ) {
    }

    /**
     * Accepts one file under `file` or several under `files[]`. A single `file`
     * keeps the legacy single-object response; `files[]` returns an
     * `attachments` list.
     */
    #[Route('/task/{taskId}/attachments', name: 'task_attachment_upload', methods: ['POST'])]
    public function __invoke(string $taskId, Request $request): JsonResponse
    {
        $task = $this->taskService->getById($taskId);

        if ($task === null) {
            return new JsonResponse(['message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->security->isGranted(TaskPermission::EDIT, $task)) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $batch = $request->files->get('files');
        $files = is_array($batch) ? $batch : array_filter([$request->files->get('file')]);

        if ($files === []) {
            return new JsonResponse(['message' => 'No valid file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                return new JsonResponse(['message' => 'No valid file uploaded'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate every file up front so a single invalid file rejects the
        // whole request before anything is written (atomic batch).
        $violations = [];

        foreach ($files as $file) {
            $fileViolations = $this->fileService->validateAttachment(
                (string) $file->getMimeType(),
                (int) $file->getSize(),
            );

            if ($fileViolations !== []) {
                $violations[] = ['file' => $file->getClientOriginalName(), 'errors' => $fileViolations];
            }
        }

        if ($violations !== []) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    // Single-file requests keep the legacy flat error shape.
                    'errors' => is_array($batch) ? $violations : $violations[0]['errors'],
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $attachments = [];

        foreach ($files as $file) {
            $metadata = $this->fileService->attach(
                Task::class,
                $taskId,
                $file->getPathname(),
                $file->getClientOriginalName(),
                (string) $file->getMimeType(),
                (int) $file->getSize(),
                $userId,
            );

            $attachments[] = $this->toArray($taskId, $metadata);
        }

        // Legacy single-object response when a lone `file` field was used.
        if (!is_array($batch)) {
            return new JsonResponse($attachments[0], Response::HTTP_CREATED);
        }

        return new JsonResponse(['attachments' => $attachments], Response::HTTP_CREATED);
    }

    /** @return array<string, mixed> */
    private function toArray(string $taskId, FileMetadataInterface $metadata): array
    {
        return [
            'id' => $metadata->getId(),
            'originalName' => $metadata->getOriginalName(),
            'mimeType' => $metadata->getMimeType(),
            'size' => $metadata->getSize(),
            'url' => '/task/' . $taskId . '/attachments/' . $metadata->getId(),
        ];
    }
}
