<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\TeamMember;

use App\TeamFeature\Application\DTORequest\TeamAddMemberRequestDTO;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AddTeamMemberController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
    ) {
    }

    #[Route('/team/{teamId}/member', name: 'team_member_add', methods: ['POST'])]
    public function __invoke(
        string $teamId,
        #[MapRequestPayload] TeamAddMemberRequestDTO $request,
    ): JsonResponse {
        try {
            $member = $this->teamService->addMember($teamId, $request);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        $profile = $member->getProfile();

        return new JsonResponse(
            [
                'teamId' => $member->getTeamId(),
                'userId' => $member->getUserId(),
                'role' => $member->getRole(),
                'joinedAt' => $member->getJoinedAt()->format(\DateTimeInterface::ATOM),
                'user' => $profile === null ? null : [
                    'userId' => $profile->getUserId(),
                    'username' => $profile->getUsername(),
                    'firstname' => $profile->getFirstname(),
                    'lastname' => $profile->getLastname(),
                    'midlname' => $profile->getMidlname(),
                    'status' => $profile->getStatus(),
                    'avatar' => $profile->getAvatar()?->getUrl(),
                ],
            ],
            Response::HTTP_CREATED,
        );
    }
}
