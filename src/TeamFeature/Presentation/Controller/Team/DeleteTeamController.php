<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\TeamFeature\Domain\Interactor\TeamDeleteInteractor;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[AsController]
final class DeleteTeamController
{
    public function __construct(
        private readonly TeamDeleteInteractor $deleteInteractor,
        private readonly TeamServiceInterface $teamService,
        private readonly Security $security,
    ) {}

    #[Route('/team/{id}', name: 'team_delete', methods: ['DELETE'])]
    public function __invoke(string $id): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        try {
            // todo: The team itself is deleted, but the list of participants remains. Change the logic.
            $this->deleteInteractor->delete($id, $userId);
            $this->teamService->deleteById($id);
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
