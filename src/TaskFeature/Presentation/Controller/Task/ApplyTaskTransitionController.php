<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeature\Domain\ValueObject\TaskPermission;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ApplyTaskTransitionController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly Security $security,
    ) {}

    #[Route('/task/{id}/transition', name: 'task_transition', methods: ['POST'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $task = $this->taskService->getById($id);

        if ($task === null) {
            return new JsonResponse(['success' => false, 'message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->security->isGranted(TaskPermission::EDIT, $task)) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $body = json_decode($request->getContent(), true);
        $transitionId = $body['transitionId'] ?? null;

        if (empty($transitionId)) {

            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Field "transitionId" is required'
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $task = $this->taskService->applyTransition($id, $transitionId);
        } catch (\DomainException $e) {

            return new JsonResponse(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return new JsonResponse(
            [
                'success' => true,
                'data' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus(),
                    'createdAt' => $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
            ],
            Response::HTTP_OK,
        );
    }
}
