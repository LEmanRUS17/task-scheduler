<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTaskListController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly Security $security,
    ) {}

    #[Route('/task', name: 'task_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $tasks = $this->taskService->getList($userId);

        return new JsonResponse(
            [
                'tasks' => array_map(
                    fn($task) => [
                        'id'            => $task->getId(),
                        'title'         => $task->getTitle(),
                        'status'        => $task->getStatus(),
                        'priority'      => $task->getPriority(),
                        'teamId'        => $task->getTeamId(),
                        'createdBy'     => $task->getCreatedBy(),
                        'assigneeIds'   => $task->getAssigneeIds(),
                        'scheduledStart' => $task->getScheduledStart()?->format(\DateTimeInterface::ATOM),
                        'scheduledEnd'  => $task->getScheduledEnd()?->format(\DateTimeInterface::ATOM),
                        'estimatedTime' => $task->getEstimatedTime(),
                        'actualTime'    => $task->getActualTime(),
                        'createdAt'     => $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    ],
                    $tasks,
                ),
            ],
            Response::HTTP_OK,
        );
    }
}
