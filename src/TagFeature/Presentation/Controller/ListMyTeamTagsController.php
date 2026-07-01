<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use App\TagFeature\Domain\Port\TeamMembershipInterface;
use App\TagFeature\Domain\ValueObject\TaggableType;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListMyTeamTagsController
{
    public function __construct(
        private readonly TagApiService $tagService,
        private readonly TeamMembershipInterface $membership,
        private readonly Security $security,
    ) {
    }

    #[Route('/my/team-tags', name: 'my_team_tags_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $teamIds = $this->membership->teamIdsOf($userId);
        $tags = $this->tagService->getTagsForEntities(TaggableType::TEAM, $teamIds);

        return new JsonResponse(['tags' => TagView::many($tags)]);
    }
}
