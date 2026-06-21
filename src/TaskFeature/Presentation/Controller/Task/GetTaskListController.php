<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TaskFeature\Presentation\Formatter\TaskResponseFormatter;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTaskListController
{
    private const MIN_QUERY_LENGTH = 2;
    private const DEFAULT_LIMIT = 10;
    private const ALLOWED_LIMITS = [10, 20, 50];

    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly SearchServiceInterface $searchService,
        private readonly TagServiceInterface $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/task', name: 'task_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $page = max(1, $request->query->getInt('page', 1));
        $limit = $this->resolveLimit($request->query->getInt('limit', self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = trim((string) $request->query->get('q', ''));

        if (strlen($query) >= self::MIN_QUERY_LENGTH) {
            [$tasks, $count] = $this->search($request, $query, $userId, $limit, $offset);
        } else {
            $tasks = $this->taskService->getPage($userId, $limit, $offset);
            $count = $this->taskService->countAll($userId);
        }

        return new JsonResponse([
            'tasks' => array_map(
                static fn(TaskDataResponseInterface $task) => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus(),
                    'status_id' => $task->getStatusId(),
                    'priority' => $task->getPriority(),
                    'teamId' => $task->getTeamId(),
                    'createdBy' => $task->getCreatedBy(),
                    'assigneeIds' => $task->getAssigneeIds(),
                    'scheduledStart' => $task->getScheduledStart()?->format(\DateTimeInterface::ATOM),
                    'scheduledEnd' => $task->getScheduledEnd()?->format(\DateTimeInterface::ATOM),
                    'estimatedTime' => $task->getEstimatedTime(),
                    'actualTime' => $task->getActualTime(),
                    'createdAt' => $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'availableTransitions' => $task->getAvailableTransitions(),
                    'description' => $task->getDescription(),
                ],
                $tasks,
            ),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($count / $limit),
            ],
            'count' => $count,
        ]);
    }

    /**
     * @return array{0: TaskDataResponseInterface[], 1: int}
     */
    private function search(Request $request, string $query, string $userId, int $limit, int $offset): array
    {
        $teamId = $request->query->get('team_id');
        $status = $request->query->get('status');

        $result = $this->searchService->searchTasks(
            $query,
            $userId,
            $teamId ?: null,
            $status ?: null,
            $limit,
            $offset,
        );

        return [$this->taskService->getByIds($result['ids']), $result['total']];
    }

    private function resolveLimit(int $limit): int
    {
        return in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : self::DEFAULT_LIMIT;
    }
}
