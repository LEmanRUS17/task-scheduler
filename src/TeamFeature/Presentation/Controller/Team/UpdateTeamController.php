<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\TeamFeature\Application\DTORequest\TeamUpdateRequestDTO;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateTeamController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
    ) {
    }

    #[Route('/team/{id}', name: 'team_update', methods: ['PATCH'])]
    public function __invoke(
        string $id,
        #[MapRequestPayload] TeamUpdateRequestDTO $request,
    ): JsonResponse {
        try {
            $team = $this->teamService->update($id, $request);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'id' => $team->getId(),
                'title' => $team->getTitle(),
                'status' => $team->getStatus(),
                'createdAt' => $team->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'description' => $team->getDescription(),
            ],
            Response::HTTP_OK,
        );
    }
}
