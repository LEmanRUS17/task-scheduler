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
final class ListTeamTaskTagsController
{
    public function __construct(
        private readonly TagApiService $tagService,
        private readonly TaskServiceInterface $taskService,
        private readonly TeamMembershipInterface $membership,
        private readonly Security $security,
    ) {
    }

    #[Route('/teams/{teamId}/task-tags', name: 'team_task_tags_list', methods: ['GET'])]
    public function __invoke(string $teamId): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        if (!$this->membership->isMember($teamId, $userId)) {
            return new JsonResponse(['message' => 'Not a team member'], Response::HTTP_FORBIDDEN);
        }

        $taskIds = array_map(
            static fn(TaskDataResponseInterface $task) => $task->getId(),
            $this->taskService->getListByTeam($teamId, $userId),
        );

        $tags = $this->tagService->getTagsForEntities(TaggableType::TASK, array_values($taskIds));

        return new JsonResponse(['tags' => TagView::many($tags)]);
    }
}
