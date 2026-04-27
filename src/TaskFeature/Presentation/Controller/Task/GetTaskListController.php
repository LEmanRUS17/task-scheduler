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
                'success' => true,
                'data' => array_map(
                    fn($task) => [
                        'id' => $task->getId(),
                        'title' => $task->getTitle(),
                        'status' => $task->getStatus(),
                        'createdAt' => $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    ],
                    $tasks,
                ),
            ],
            Response::HTTP_OK,
        );
    }
}
