<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\TeamMember;

use App\TeamFeature\Presentation\Formatter\TeamMemberFormatter;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class SearchTeamUsersController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/{teamId}/users', name: 'team_users_list', methods: ['POST'])]
    public function __invoke(string $teamId, Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $currentUserId = $securityUser->getDomainUser()->id()->value();

        $name = $request->request->get('name');

        try {
            $members = $this->teamService->searchMembers($teamId, $currentUserId, $name);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse([
            'users' => array_map(
                static fn($member) => TeamMemberFormatter::formatUser($member),
                $members,
            ),
        ], Response::HTTP_OK);
    }
}
