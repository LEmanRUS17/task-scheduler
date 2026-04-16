<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller;

use App\TeamFeature\Application\DTORequest\TeamCreateRequestDTO;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class CreateTeamController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {}

    #[Route('/team/create', name: 'team_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] TeamCreateRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $creatorUserId = $securityUser->getDomainUser()->id()->value();

        try {
            $team = $this->teamService->create($request, $creatorUserId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return new JsonResponse(
            [
                'success' => true,
                'variant' => 'success',
                'data' => [
                    'id' => $team->getId(),
                    'title' => $team->getTitle(),
                    'status' => $team->getStatus(),
                ],
            ],
            Response::HTTP_CREATED,
        );
    }
}
