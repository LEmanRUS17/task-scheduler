<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TeamFeature\Domain\Interactor\TeamGetInteractor;
use App\TeamFeature\Presentation\Formatter\TeamMemberFormatter;
use App\TeamFeature\Presentation\Formatter\TeamResponseFormatter;
use App\TeamFeature\Presentation\Formatter\TeamTagFormatter;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[AsController]
final class GetTeamController
{
    public function __construct(
        private readonly TeamGetInteractor $getInteractor,
        private readonly TeamServiceInterface $teamService,
        private readonly TagServiceInterface $tagService,
        private readonly Security $security,
    ) {}

    #[Route('/team/{id}', name: 'team_get', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $this->getInteractor->get($id, $userId);

        $team = $this->teamService->getById($id);

        if ($team === null) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Team not found'
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        $tagsByTask = $this->tagService->getEntityTagsByIds(TagServiceInterface::TYPE_TEAM, [$id]);

        $team = TeamResponseFormatter::format($team, $tagsByTask[$id] ?? []);

        $members = $this->teamService->getMembers($id);

        $team['members'] = array_map(
            function ($member) {
                return TeamMemberFormatter::format($member);
            },
            $members
        );

        $tags = $this->tagService->getEntityTagsById(TagServiceInterface::TYPE_TEAM, $id);

        $team['tags']  = array_map(
            function ($tag) {
                return TeamTagFormatter::format($tag);
            },
            $tags
        );

        return new JsonResponse($team, Response::HTTP_OK);
    }
}
