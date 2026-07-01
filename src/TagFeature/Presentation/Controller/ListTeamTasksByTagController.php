<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use App\TagFeature\Domain\Port\TeamMembershipInterface;
use App\TagFeature\Domain\ValueObject\TaggableType;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListTeamTasksByTagController
{
    public function __construct(
        private readonly TagApiService $tagService,
        private readonly TaskServiceInterface $taskService,
        private readonly TeamMembershipInterface $membership,
        private readonly Security $security,
    ) {
    }

    #[Route('/teams/{teamId}/tasks/by-tag/{tagId}', name: 'team_tasks_by_tag', methods: ['GET'])]
    public function __invoke(string $teamId, string $tagId): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        if (!$this->membership->isMember($teamId, $userId)) {
            return new JsonResponse(['message' => 'Not a team member'], Response::HTTP_FORBIDDEN);
        }

        try {
            $taggedIds = $this->tagService->getEntityIdsByTag(TaggableType::TASK, $tagId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $taggedSet = array_flip($taggedIds);

        $tasks = array_filter(
            $this->taskService->getListByTeam($teamId, $userId),
            static fn(TaskDataResponseInterface $task) => isset($taggedSet[$task->getId()]),
        );

        return new JsonResponse([
            'tasks' => array_values(array_map(
                static fn(TaskDataResponseInterface $task) => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus(),
                    'priority' => $task->getPriority(),
                    'teamId' => $task->getTeamId(),
                ],
                $tasks,
            )),
        ]);
    }
}
