<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeature\Presentation\Formatter\TaskResponseFormatter;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTeamTaskListController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/{teamId}/task', name: 'team_task_list', methods: ['GET'])]
    public function __invoke(string $teamId): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $tasks = $this->taskService->getListByTeam($teamId, $userId);
        } catch (\DomainException) {
            return new JsonResponse(
                ['success' => false, 'message' => 'Access denied.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        return new JsonResponse(
            [
                'tasks' => array_map(
                    static fn($task) => TaskResponseFormatter::format($task),
                    $tasks,
                ),
            ],
            Response::HTTP_OK,
        );
    }
}
