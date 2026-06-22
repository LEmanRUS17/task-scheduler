<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DownloadTaskAttachmentController
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {
    }

    #[Route('/task/{taskId}/attachments/{fileId}', name: 'task_attachment_download', methods: ['GET'])]
    public function __invoke(string $taskId, string $fileId): Response
    {
        $metadata = $this->fileService->getFile($fileId);

        if (
            $metadata === null
            || $metadata->getEntityClass() !== Task::class
            || $metadata->getEntityId() !== $taskId
        ) {
            return new JsonResponse(['message' => 'Attachment not found'], Response::HTTP_NOT_FOUND);
        }

        $path = $this->fileService->absolutePath($fileId);

        if ($path === null || !is_file($path)) {
            return new JsonResponse(['message' => 'Attachment not found'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $metadata->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $metadata->getOriginalName(),
        );

        return $response;
    }
}
