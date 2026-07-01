<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use App\TagFeature\Domain\Port\TeamMembershipInterface;
use App\TagFeature\Domain\ValueObject\TaggableType;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListMyTeamsByTagController
{
    public function __construct(
        private readonly TagApiService $tagService,
        private readonly TeamServiceInterface $teamService,
        private readonly TeamMembershipInterface $membership,
        private readonly Security $security,
    ) {
    }

    #[Route('/my/teams/by-tag/{tagId}', name: 'my_teams_by_tag', methods: ['GET'])]
    public function __invoke(string $tagId): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $taggedIds = $this->tagService->getEntityIdsByTag(TaggableType::TEAM, $tagId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $memberSet = array_flip($this->membership->teamIdsOf($userId));
        $teamIds = array_values(array_filter($taggedIds, static fn(string $id) => isset($memberSet[$id])));

        $teams = $this->teamService->getByIds($teamIds);

        return new JsonResponse([
            'teams' => array_values(array_map(
                static fn(TeamDataResponseInterface $team) => [
                    'id' => $team->getId(),
                    'title' => $team->getTitle(),
                    'status' => $team->getStatus(),
                ],
                $teams,
            )),
        ]);
    }
}
