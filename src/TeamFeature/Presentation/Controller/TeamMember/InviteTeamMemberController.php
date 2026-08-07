<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\TeamMember;

use App\TeamFeature\Application\DTORequest\TeamInviteMemberRequestDTO;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class InviteTeamMemberController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/{teamId}/invitation', name: 'team_invitation_create', methods: ['POST'])]
    public function __invoke(
        string $teamId,
        #[MapRequestPayload] TeamInviteMemberRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $invitedByUserId = $securityUser->getDomainUser()->id()->value();

        try {
            $invitation = $this->teamService->inviteMember($teamId, $request, $invitedByUserId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(
            [
                'id' => $invitation->getId(),
                'teamId' => $invitation->getTeamId(),
                'invitedUserId' => $invitation->getInvitedUserId(),
                'invitedByUserId' => $invitation->getInvitedByUserId(),
                'role' => $invitation->getRole(),
                'status' => $invitation->getStatus(),
                'createdAt' => $invitation->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'expiresAt' => $invitation->getExpiresAt()->format(\DateTimeInterface::ATOM),
            ],
            Response::HTTP_CREATED,
        );
    }
}
