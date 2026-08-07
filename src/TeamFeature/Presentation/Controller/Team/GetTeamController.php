<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TeamFeature\Presentation\Formatter\TeamMemberFormatter;
use App\TeamFeature\Presentation\Formatter\TeamResponseFormatter;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[AsController]
final class GetTeamController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly TagServiceInterface $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/{id}', name: 'team_get', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $team = $this->teamService->getByIdForUser($id, $userId);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        $tagsByTeam = $this->tagService->getEntityTagsByIds(TagServiceInterface::TYPE_TEAM, [$id]);

        $team = TeamResponseFormatter::format($team, $tagsByTeam[$id] ?? []);

        $members = $this->teamService->getMembers($id);

        $team['members'] = array_map(
            function ($member) {
                return TeamMemberFormatter::format($member);
            },
            $members
        );

        return new JsonResponse($team, Response::HTTP_OK);
    }
}
