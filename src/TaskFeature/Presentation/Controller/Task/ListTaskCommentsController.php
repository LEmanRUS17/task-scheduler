<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\CommentFeatureApi\Contract\CommentServiceInterface;
use App\TaskFeature\Presentation\Formatter\TaskCommentFormatter;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListTaskCommentsController
{
    private const DEFAULT_LIMIT = 10;
    private const ALLOWED_LIMITS = [10, 20, 50];

    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly CommentServiceInterface $commentService,
    ) {
    }

    // The literal "task" segment takes precedence over the generic "/comment/{entityType}/{entityId}"
    // route, adding the task-existence check on top of it.
    #[Route('/comment/task/{id}', name: 'task_comments_list', methods: ['GET'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($this->taskService->getById($id) === null) {
            return new JsonResponse(['message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = $this->resolveLimit($request->query->getInt('limit', self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $comments = $this->commentService->getEntityCommentThread(
            TaskServiceInterface::COMMENTABLE_TYPE,
            $id,
            $limit,
            $offset,
        );
        $rootCount = $this->commentService->countEntityComments(TaskServiceInterface::COMMENTABLE_TYPE, $id);
        $count = $this->commentService->countAllEntityComments(TaskServiceInterface::COMMENTABLE_TYPE, $id);

        return new JsonResponse([
            'comments' => TaskCommentFormatter::formatMany($comments),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                // Pagination slices root comments; their replies ride along with the page.
                'pages' => (int) ceil($rootCount / $limit),
            ],
            'count' => $count,
        ]);
    }

    private function resolveLimit(int $limit): int
    {
        return in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : self::DEFAULT_LIMIT;
    }
}
