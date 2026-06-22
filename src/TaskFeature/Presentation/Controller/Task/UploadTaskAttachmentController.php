<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

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

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return new JsonResponse(['message' => 'No valid file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $metadata = $this->fileService->attach(
                Task::class,
                $taskId,
                $file->getPathname(),
                $file->getClientOriginalName(),
                (string) $file->getMimeType(),
                (int) $file->getSize(),
                $userId,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return new JsonResponse(
            [
                'id' => $metadata->getId(),
                'originalName' => $metadata->getOriginalName(),
                'mimeType' => $metadata->getMimeType(),
                'size' => $metadata->getSize(),
                'url' => '/task/' . $taskId . '/attachments/' . $metadata->getId(),
            ],
            Response::HTTP_CREATED,
        );
    }
}
