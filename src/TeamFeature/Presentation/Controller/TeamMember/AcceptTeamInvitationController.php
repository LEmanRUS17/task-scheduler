<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\TeamMember;

use App\TeamFeature\Application\DTORequest\TeamAcceptInvitationRequestDTO;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AcceptTeamInvitationController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/invitation/accept', name: 'team_invitation_accept', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] TeamAcceptInvitationRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            $member = $this->teamService->acceptInvitation($request, $userId);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(
            [
                'teamId' => $member->getTeamId(),
                'userId' => $member->getUserId(),
                'role' => $member->getRole(),
                'joinedAt' => $member->getJoinedAt()->format(\DateTimeInterface::ATOM),
            ],
            Response::HTTP_OK,
        );
    }
}
